"""
Projet — Estimation du prix d'un vin par apprentissage automatique.

Usage :
    python main.py scrape    # produit vins.csv
    python main.py clean     # produit vins_clean.csv
    python main.py learn     # exécute les expériences
    python main.py all       # tout enchaîner
"""

import csv
import sys
import time
import threading
import subprocess
import json
import numpy as np
import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
import os

from urllib.parse import urljoin
from concurrent.futures import ThreadPoolExecutor, as_completed
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import (
    NoSuchElementException, InvalidSessionIdException,
    WebDriverException, TimeoutException,
)
from bs4 import BeautifulSoup
from sklearn.linear_model import LinearRegression
from sklearn.tree import DecisionTreeRegressor
from sklearn.neighbors import KNeighborsRegressor
from sklearn.ensemble import RandomForestRegressor
from sklearn.decomposition import PCA
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.preprocessing import MinMaxScaler, StandardScaler
from sklearn.pipeline import make_pipeline

# SCRAPING (PARALLÉLISÉ)

os.makedirs("data", exist_ok=True)
os.makedirs("figures", exist_ok=True)

URL_BASE = "https://www.millesima.fr"
URL_LISTE = URL_BASE + "/bordeaux.html?page={page}"

def _build_driver():
    """Crée un Chrome headless avec les optimisations de vitesse."""
    options = Options()
    options.page_load_strategy = 'eager'
    options.add_argument("--disable-application-cache")
    options.add_argument("--disable-cache")
    options.add_argument("--media-cache-size=1")
    options.add_argument("--disk-cache-size=1")
    options.add_argument("--headless=new")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--window-size=1920,1080")
    options.add_argument("--disable-extensions")
    options.add_argument("--blink-settings=imagesEnabled=false")
    options.add_argument(
        "user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
        "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
    )
    options.add_experimental_option("prefs", {
        "profile.managed_default_content_settings.images": 2,
        "profile.default_content_setting_values.notifications": 2,
    })
    return webdriver.Chrome(options=options)

_tls = threading.local()

def _driver_local():
    """Driver propre au thread courant, créé à la demande."""
    if not hasattr(_tls, "driver"):
        _tls.driver = _build_driver()
        _tls.cookies_done = False
        _tls.compteur = 0
    return _tls.driver

def _redemarrer_driver_tls():
    if hasattr(_tls, "driver"):
        try:
            _tls.driver.quit()
        except Exception:
            pass
    _tls.driver = _build_driver()
    _tls.cookies_done = False

def _accepter_cookies(driver):
    if getattr(_tls, "cookies_done", False):
        return
    for xp in ["//button[contains(., 'Continuer sans accepter')]",
               "//button[contains(., 'Accepter')]"]:
        try:
            driver.find_element(By.XPATH, xp).click()
            time.sleep(0.5)
            break
        except NoSuchElementException:
            continue
    _tls.cookies_done = True

def getsoup(url, driver=None):
    """Renvoie la BeautifulSoup de la page."""
    if driver is None:
        driver = _driver_local()
    driver.get(url)
    _accepter_cookies(driver)
    WebDriverWait(driver, 10).until(
        EC.any_of(
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, "[class*='ProductAttributesTable_']")),
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, "[class*='ProductListCU_']")),
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, "[class*='ProductView_']")),
        )
    )
    return BeautifulSoup(driver.page_source, "lxml")

# ---------------------------------------------------------------------------
# Extraction via __NEXT_DATA__
# ---------------------------------------------------------------------------

def _parse_note_json(valeur):
    """Convertit '98-100', '17.5', '100' en float. Gère les fourchettes."""
    if valeur is None:
        return None
    try:
        s = str(valeur).strip()
        if "-" in s:
            a, b = s.split("-")
            return (float(a) + float(b)) / 2
        return float(s)
    except (ValueError, IndexError):
        return None

def informations(soup):
    """Extrait Appellation, Parker, Robinson, Suckling, Prix du JSON __NEXT_DATA__."""
    script = soup.find("script", id="__NEXT_DATA__")
    if not script:
        return None
    
    contenu = script.string or script.get_text()
    if not contenu:
        return None
    
    try:
        data = json.loads(contenu)
    except json.JSONDecodeError:
        return None
    
    try:
        content = data["props"]["pageProps"]["initialReduxState"]["product"]["content"]
    except (KeyError, TypeError):
        return None
    
    attrs = content.get("attributes", {})
    
    app_obj = attrs.get("appellation")
    appellation = app_obj.get("value") if app_obj else None
    
    def _note(cle):
        obj = attrs.get(cle)
        return _parse_note_json(obj.get("value")) if obj else None
    
    parker = _note("note_rp")
    robinson = _note("note_jr")
    suckling = _note("note_js")
    prix = content.get("minPrice")
    
    champs = [appellation, parker, robinson, suckling, prix]
    return ",".join("" if v is None else str(v) for v in champs)

# ---------------------------------------------------------------------------
# Pagination
# ---------------------------------------------------------------------------

_SLUGS_EXCLUS = {
    "premium", "bordeaux", "bourgogne", "champagne", "rhone", "loire",
    "alsace", "languedoc", "provence", "italie", "espagne", "portugal",
    "haut-medoc", "medoc", "pauillac", "saint-julien", "saint-estephe",
    "margaux", "moulis", "listrac", "pessac-leognan", "graves", "sauternes",
    "barsac", "saint-emilion", "pomerol", "lalande-de-pomerol", "fronsac",
    "canon-fronsac", "blaye", "bourg", "primeurs", "spiritueux",
    "panier", "compte", "connexion", "contact", "livraison", "cgv",
    "mentions-legales", "confidentialite", "cookies", "blog",
}

def _liens_vins(soup):
    liens = set()
    for a in soup.find_all("a", href=True):
        href = a["href"].split("?")[0].split("#")[0]
        if not href.endswith(".html") or href.count("/") != 1:
            continue
        slug = href[1:-5]
        if slug and slug not in _SLUGS_EXCLUS:
            liens.add(urljoin(URL_BASE, href))
    return liens

def _scraper_une_fiche(url):
    """Worker : renvoie une liste de champs ou None si à ignorer."""
    for _ in range(3):
        try:
            # Compteur de redémarrage préventif, par thread.
            if not hasattr(_tls, "compteur"):
                _tls.compteur = 0
            _tls.compteur += 1
            if _tls.compteur % 50 == 0:
                _redemarrer_driver_tls()
            
            soup = getsoup(url)
            ligne = (informations(soup) or ",,,,").split(",")
            time.sleep(1.0)
            return ligne if ligne[0].strip() else None
        except (InvalidSessionIdException, WebDriverException, TimeoutException):
            _redemarrer_driver_tls()
    return None

def scraper_bordeaux(chemin_csv = "vins.csv", n_workers = 1):
    """Scrape toutes les fiches Bordeaux en parallèle et écrit le CSV."""
    # Phase 1 : collecte des URLs.
    driver = _build_driver()
    urls = set()
    try:
        p = 1
        while True:
            try:
                sp = getsoup(URL_LISTE.format(page=p), driver=driver)
            except TimeoutException:
                break  # 404 ou plus de pages
            nouvelles = _liens_vins(sp)
            if not nouvelles - urls:
                break
            urls |= nouvelles
            print(f"[info] page {p} — {len(urls)} fiches connues.")
            p += 1
    finally:
        try:
            driver.quit()
        except Exception:
            pass

    urls = sorted(urls)
    print(f"[info] Total : {len(urls)} fiches, {n_workers} workers.")

    # Phase 2 : scraping parallèle.
    lock = threading.Lock()
    fait = ignorees = 0
    t0 = time.time()

    with open(chemin_csv, "w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(["Appellation", "Robert", "Robinson", "Suckling", "Prix"])

        with ThreadPoolExecutor(max_workers=n_workers) as pool:
            futures = [pool.submit(_scraper_une_fiche, u) for u in urls]
            for fut in as_completed(futures):
                ligne = fut.result()
                with lock:
                    fait += 1
                    if ligne is None:
                        ignorees += 1
                    else:
                        w.writerow(ligne)
                        f.flush()
                    if fait % 25 == 0:
                        dt = time.time() - t0
                        print(f"[info] {fait}/{len(urls)} "
                              f"({ignorees} ignorées, "
                              f"{fait/dt:.2f} fiches/s)")

    # Cleanup : taskkill nettoie les drivers thread-local restants.
    subprocess.run(["taskkill", "/F", "/IM", "chromedriver.exe"], capture_output=True)
    subprocess.run(["taskkill", "/F", "/IM", "chrome.exe"], capture_output=True)
    print(f"[ok] CSV écrit dans {chemin_csv} : ({fait - ignorees} fiches valides et {ignorees} ignorées).")

# NETTOYAGE

CRITIQUES = ["Robert", "Robinson", "Suckling"]

def ascii_only(s):
    return s.encode("ascii", "ignore").decode("ascii") if isinstance(s, str) else s

def moyenne_par_appellation(vins, critique):
    moy = (vins.groupby("Appellation")[critique].mean().reset_index()
                .rename(columns={critique: critique + "_moy"}))
    moy[critique + "_moy"] = moy[critique + "_moy"].fillna(0)
    return moy

def nettoyer(chemin_csv="vins.csv", chemin_sortie="vins_clean.csv"):
    vins = pd.read_csv(chemin_csv)
    print(f"{len(vins)} lignes importées.")

    vins["Prix"] = vins["Prix"].astype(str).map(ascii_only)
    vins["Prix"] = pd.to_numeric(vins["Prix"], errors="coerce")
    vins = vins.dropna(subset=["Prix"]).reset_index(drop=True)
    print("Prix nettoyé.")

    for c in CRITIQUES:
        moy = moyenne_par_appellation(vins, c)
        vins = vins.merge(moy, on="Appellation", how="left")
        vins[c] = vins[c].fillna(vins[c + "_moy"]).fillna(0)
        vins = vins.drop(columns=[c + "_moy"])
    print("Notes complétées.")

    vins = pd.get_dummies(vins, columns=["Appellation"], prefix="App", dtype=int)
    print(f"DataFrame final : {vins.shape}.")

    vins.to_csv(chemin_sortie, index=False)
    print(f"[ok] {chemin_sortie} écrit.")
    return vins

# APPRENTISSAGE

def preparer_donnees(vins, log=False, random_state=49):
    X = vins.drop(columns=["Prix"]).values
    y = vins["Prix"].values
    if log:
        y = np.log(y)
    return train_test_split(X, y, test_size=0.25, random_state=random_state)

def evaluer(modele, X_tr, y_tr, X_te, y_te, pre=None):
    if pre == "norm":
        modele = make_pipeline(MinMaxScaler(), modele)
    elif pre == "std":
        modele = make_pipeline(StandardScaler(), modele)
    modele.fit(X_tr, y_tr)
    return modele.score(X_te, y_te), modele.predict(X_te)

def figure_visualisation(y_test, y_pred, titre, chemin):
    plt.figure(figsize=(6, 6))
    plt.scatter(y_pred, y_test, alpha=0.4, s=15)
    lo = min(y_pred.min(), y_test.min())
    hi = max(y_pred.max(), y_test.max())
    plt.plot([lo, hi], [lo, hi], "r--")
    plt.xlabel("Estimation")
    plt.ylabel("Prix réel")
    plt.title(titre)
    plt.tight_layout()
    plt.savefig(chemin, dpi=120)
    plt.close()

def meilleure_profondeur(X_tr, y_tr):
    scores = {h: cross_val_score(DecisionTreeRegressor(max_depth=h, random_state=49),
                                  X_tr, y_tr, cv=5, scoring="r2").mean()
              for h in (3, 4, 5)}
    h = max(scores, key=scores.get)
    print(f"CV : {scores} -> h = {h}")
    return h

def _fabriquer(nom, h):
    if nom == "LR":
        return LinearRegression()
    if nom == "AD":
        return DecisionTreeRegressor(max_depth=h, random_state=49)
    return KNeighborsRegressor(n_neighbors=4)

def apprentissage(chemin_csv="vins_clean.csv"):
    vins = pd.read_csv(chemin_csv)
    print(f"Dataset : {vins.shape}.\n")

    pmin, pmax = vins["Prix"].min(), vins["Prix"].max()
    print(f"Prix min={pmin:.2f}, max={pmax:.2f} (rapport {pmax/pmin:.0f}).")
    log = (pmax / pmin) > 100
    if log:
        print(f"log : min={np.log(pmin):.2f}, max={np.log(pmax):.2f}.")

    X_tr, X_te, y_tr, y_te = preparer_donnees(vins, log)
    resultats = {}

    for pre in [None, "norm", "std"]:
        s, yp = evaluer(LinearRegression(), X_tr, y_tr, X_te, y_te, pre)
        resultats[("LR", pre)] = s
        nom = {None: "LR", "norm": "Norm+LR", "std": "Std+LR"}[pre]
        figure_visualisation(y_te, yp, f"{nom} (r2={s:.3f})",
                             f"figures/vis_{nom.replace('+','_')}.png")
        print(f"[LR] {nom:>10s} -> r2 = {s:.4f}")

    h = meilleure_profondeur(X_tr, y_tr)
    for pre in [None, "norm", "std"]:
        s, _ = evaluer(DecisionTreeRegressor(max_depth=h, random_state=49),
                       X_tr, y_tr, X_te, y_te, pre)
        resultats[("AD", pre)] = s
        nom = "AD" if pre is None else pre + "+AD"
        print(f"[AD] {nom:>10s} -> r2 = {s:.4f}")

    for k in (4, 5):
        s, _ = evaluer(KNeighborsRegressor(n_neighbors=k), X_tr, y_tr, X_te, y_te)
        print(f"KNN {'k='+str(k):>10s} -> r2 = {s:.4f}")
    for pre in [None, "norm", "std"]:
        s, _ = evaluer(KNeighborsRegressor(n_neighbors=4), X_tr, y_tr, X_te, y_te, pre)
        resultats[("KNN", pre)] = s
        nom = "KNN" if pre is None else pre + "+KNN"
        print(f"[KNN] {nom:>10s} -> r2 = {s:.4f}")

    meilleurs = {m: max(s for (mm, _), s in resultats.items() if mm == m)
                 for m in ("LR", "AD", "KNN")}
    M_nom = max(meilleurs, key=meilleurs.get)
    print(f"\nBest : {meilleurs} => {M_nom}")

    pca = PCA(n_components=5).fit(X_tr)
    print(f"\nPCA(5) : variance = {pca.explained_variance_ratio_.sum():.2%}")
    s, _ = evaluer(_fabriquer(M_nom, h), pca.transform(X_tr), y_tr, pca.transform(X_te), y_te)
    print(f"{M_nom} sur PCA -> r2 = {s:.4f}")

    corr = vins.corr()
    fig, ax = plt.subplots(figsize=(14, 12))
    sns.heatmap(corr, cmap="coolwarm", center=0, ax=ax)
    plt.tight_layout()
    plt.savefig("figures/correlation.png", dpi=120)
    plt.close()
    top5 = corr["Prix"].drop("Prix").abs().sort_values(ascending=False).head(5).index.tolist()
    print(f"\nTop-5 corrélés au prix :")
    for a in top5:
        print(f"  {a:30s} corr = {corr['Prix'][a]:+.3f}")
    Xtr5, Xte5, ytr5, yte5 = preparer_donnees(vins[top5 + ["Prix"]], log)
    s, _ = evaluer(_fabriquer(M_nom, h), Xtr5, ytr5, Xte5, yte5)
    print(f"{M_nom} sur top-5 -> r2 = {s:.4f}")

    print("\nRandom Forest :")
    for pre in [None, "norm", "std"]:
        s, _ = evaluer(RandomForestRegressor(n_estimators=200, random_state=49),
                       X_tr, y_tr, X_te, y_te, pre)
        nom = "RF" if pre is None else pre + "+RF"
        print(f"  {nom:>10s} -> r2 = {s:.4f}")

def main(argv):
    if len(argv) < 2 or argv[1] not in {"scrape", "clean", "learn", "all"}:
        print(__doc__)
        return
    if argv[1] in {"scrape", "all"}:
        scraper_bordeaux("data/vins.csv")
    if argv[1] in {"clean", "all"}:
        nettoyer("data/vins.csv", "data/vins_clean.csv")
    if argv[1] in {"learn", "all"}:
        apprentissage("data/vins_clean.csv")

if __name__ == "__main__":
    main(sys.argv)