import os
import unicodedata
import requests
import numpy as np
import csv
import time
import pandas as pd
import matplotlib.pyplot as plt

from bs4 import BeautifulSoup
from sklearn.model_selection import train_test_split
from sklearn.linear_model import LinearRegression
from sklearn.pipeline import make_pipeline
from sklearn.preprocessing import MinMaxScaler, StandardScaler
from sklearn.tree import DecisionTreeRegressor
from sklearn.neighbors import KNeighborsRegressor
from sklearn.decomposition import PCA
from concurrent.futures import ThreadPoolExecutor

base_url = "https://www.immo-entre-particuliers.com"
session = requests.Session()
session.headers.update({
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                  "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36"
})

DOSSIER = os.path.dirname(os.path.abspath(__file__))
DATA_DIR = os.path.join(DOSSIER, "data")
FIGURES_DIR = os.path.join(DOSSIER, "figures")

class NonValide(Exception):
    pass

def getSoup(url, max_retries=3):
    for attempt in range(max_retries):
        try:
            response = session.get(url, timeout=30)
            response.raise_for_status()
            return BeautifulSoup(response.text, 'lxml')
        except (requests.exceptions.Timeout,
                requests.exceptions.ConnectionError) as e:
            if attempt < max_retries - 1:
                wait = 2 ** attempt  # 1s, 2s, 4s
                print(f"  ⏱️  Erreur réseau, retry dans {wait}s ({attempt+1}/{max_retries})")
                time.sleep(wait)
            else:
                raise

def prix(soup):
    price_tag = soup.find(class_="product-price")
    if not price_tag:
        raise NonValide("Prix introuvable")
    price = price_tag.get_text(strip=True).replace("€", "").replace(" ", "")
    if not price.isdigit() or int(price) < 10000:
        raise NonValide(f"Prix invalide ou trop bas ({price})")
    return str(price)

def ville(soup):
    location_tag = soup.find(class_="mt-0")
    if not location_tag:
        raise NonValide("Ville introuvable")
    location_text = location_tag.get_text(strip=True)
    index = location_text.rfind(", ")
    if index == -1:
        raise NonValide("Format de ville invalide")
    return location_text[index + 2:]

def caracteristiques(soup, label, default="-"):
    label_span = soup.find("span", class_="text-muted", string=label)
    if not label_span:
        return default
    container = label_span.find_parent("li")
    if not container:
        return default
    value_span = container.find("span", class_="fw-bold")
    return value_span.get_text(strip=True) if value_span else default

def type_bien(soup):
    value = caracteristiques(soup, "Type")
    if not value:
        raise NonValide("Type de bien introuvable")
    if value not in ("Maison", "Appartement"):
        raise NonValide(f"Type non conforme ({value})")
    return value

def surface(soup):
    return caracteristiques(soup, "Surface").replace("m²", "").replace(" ", "")

def nbrpieces(soup):
    return caracteristiques(soup, "Nb. de pièces")

def nbrchambres(soup):
    return caracteristiques(soup, "Nb. de chambres")

def nbrsdb(soup):
    return caracteristiques(soup, "Nb. de salles de bains")

def dpe(soup):
    return caracteristiques(soup, "DEP").split()[0]

def informations(soup):
    return ",".join([
        ville(soup),
        type_bien(soup),
        surface(soup),
        nbrpieces(soup),
        nbrchambres(soup),
        nbrsdb(soup),
        dpe(soup),
        prix(soup)
    ])

def total_pages(url):
    soup = getSoup(url)
    pagination = soup.find("ul", class_="pagination")
    pages = [int(a.get_text(strip=True)) for a in pagination.find_all("a") if a.get_text(strip=True).isdigit()]
    return max(pages) if pages else 1

def fetch_annonce(annonce_url):
    try:
        soup = getSoup(f"{base_url}{annonce_url}")
        return informations(soup)
    except (NonValide, requests.exceptions.RequestException):
        return None
    
def scrape_page(url, page, max_page):
    for attempt in range(3):
        try:
            soup = getSoup(f"{url}/{page}")
            urls = list({
                a.get("href") for a in soup.find_all("a", href=True)
                if a.get("href", "").startswith("/annonce-")
            })
            with ThreadPoolExecutor(max_workers=15) as executor:
                annonces = [r for r in executor.map(fetch_annonce, urls) if r is not None]
            print(f"Page {page}/{max_page} — {len(annonces)} annonces récupérées")
            return annonces
        except Exception as e:
            if attempt < 2:
                wait = 5 * (attempt + 1)
                print(f"  ⚠️ Page {page} échouée (essai {attempt+1}/3) : {e} — réessai dans {wait}s...")
                time.sleep(wait)
            else:
                print(f"  ❌ Page {page} abandonnée après 3 essais")
                return []

def save(data_list):
    os.makedirs(DATA_DIR, exist_ok=True)
    with open(os.path.join(DATA_DIR, "annonces.csv"), "w", newline="", encoding="utf-8") as file:
        writer = csv.writer(file)
        writer.writerow(["Ville", "Type", "Surface", "NbrPieces", "NbrChambres", "NbrSdb", "DPE", "Prix"])
        for data in data_list:
            writer.writerow(data.split(","))

def scrape(url):
    data_list = []
    max_page = total_pages(url)
    start = time.time()

    try:
        for page in range(1, max_page + 1):
            annonces = scrape_page(url, page, max_page)
            data_list.extend(annonces)
            save(data_list)
    except KeyboardInterrupt:
        print(f"\n⚠️ Scraping interrompu — {len(data_list)} annonces sauvegardées")
        save(data_list)

    elapsed = time.time() - start
    print(f"\nScraping terminé : {len(data_list)} annonces en {elapsed:.1f}s")

def normaliser(serie):
    serie = serie.apply(lambda x: ''.join(
        c for c in unicodedata.normalize('NFD', str(x))
        if unicodedata.category(c) != 'Mn'
    ))
    return (
        serie.str.lower()
             .str.replace(r"[\s\-'']", "", regex=True)
             .str.replace(r"(paris).*", "paris", regex=True)
             .str.replace(r"saint(?!s)", "st", regex=True)
             .str.replace(r".*(saints).*", "saints", regex=True)
             .str.replace(r"(lechesnay).*", "lechesnay", regex=True)
             .str.replace(r"(eragny).*", "eragny", regex=True)
             .str.replace(r".*(courcouronnes)", "courcouronnes", regex=True)
             .str.replace(r"(evry).*", "evry", regex=True)
             .str.replace(r"(perigny).*", "perigny", regex=True)
             .str.replace(r"(franconville).*", "franconville", regex=True)
    )

def main():
    annonces = pd.read_csv(os.path.join(DATA_DIR, 'annonces.csv'))
    avant = len(annonces)
    annonces = annonces.drop_duplicates()
    print(f"  \n{avant - len(annonces)} doublons supprimés ({len(annonces)} restantes)")
    annonces['DPE'] = annonces['DPE'].replace("-", "Vierge")

    for col in ['Surface', 'NbrPieces', 'NbrChambres', 'NbrSdb']:
        annonces[col] = pd.to_numeric(annonces[col].replace('-', pd.NA), errors='coerce')
        moyenne = annonces[col].mean()
        if pd.isna(moyenne):
            moyenne = 0
        annonces[col] = annonces[col].fillna(round(moyenne))
        annonces[col] = annonces[col].astype(int)

    # Filtrage des valeurs aberrantes
    aberrantes = annonces[ (annonces['Surface'] < 10) | (annonces['NbrPieces'] > 10) ]
    print(f"  \n{len(aberrantes)} annonces aberrantes supprimées :")
    print(aberrantes[['Ville', 'Type', 'Surface', 'NbrPieces', 'NbrChambres', 'NbrSdb', 'Prix']].to_string())
    annonces = annonces.drop(aberrantes.index)
    print(f"  {len(annonces)} restantes")

    annonces = pd.get_dummies(annonces, columns=["Type", "DPE"], prefix=["Type", "DPE"], dtype=int)

    villes = pd.read_csv(os.path.join(DATA_DIR, 'cities.csv'), low_memory=False)

    villes = villes[villes["reg_nom"].str.lower() == "île-de-france"].copy()
    annonces['Ville'] = normaliser(annonces['Ville'])
    villes['nom_standard'] = normaliser(villes['nom_standard'])

    villes = villes.drop_duplicates(subset='nom_standard', keep='first')
    annonces_merged = annonces.merge(
        villes[['nom_standard', 'latitude_centre', 'longitude_centre']],
        left_on='Ville', right_on='nom_standard', how='left'
    )
    annonces = annonces_merged.drop(columns=['Ville', 'nom_standard'])
    annonces = annonces.dropna(subset=['latitude_centre', 'longitude_centre'])
    pd.set_option('display.max_rows', None)

    X = annonces.drop('Prix', axis=1)
    y = annonces['Prix']
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.25, random_state=49)

    lr = LinearRegression()
    lr.fit(X_train, y_train)
    r2_lr = lr.score(X_test, y_test)

    pipe_lr_norm = make_pipeline(MinMaxScaler(), LinearRegression())
    pipe_lr_norm.fit(X_train, y_train)
    r2_lr_norm = pipe_lr_norm.score(X_test, y_test)

    pipe_lr_std = make_pipeline(StandardScaler(), LinearRegression())
    pipe_lr_std.fit(X_train, y_train)
    r2_lr_std = pipe_lr_std.score(X_test, y_test)

    # recherche du meilleur max_depth
    best_depth = 4
    best_score_ad = -np.inf
    for depth in [3, 4, 5, 6, 8, 10]:
        ad_test = DecisionTreeRegressor(max_depth=depth, random_state=49)
        ad_test.fit(X_train, y_train)
        score = ad_test.score(X_test, y_test)
        if score > best_score_ad:
            best_score_ad = score
            best_depth = depth
    print(f"  \nMeilleur max_depth : {best_depth} (R²={best_score_ad:.4f})")

    ad = DecisionTreeRegressor(max_depth=best_depth, random_state=49)
    ad.fit(X_train, y_train)
    r2_ad = ad.score(X_test, y_test)

    pipe_ad_norm = make_pipeline(MinMaxScaler(), DecisionTreeRegressor(max_depth=best_depth, random_state=49))
    pipe_ad_norm.fit(X_train, y_train)
    r2_ad_norm = pipe_ad_norm.score(X_test, y_test)

    pipe_ad_std = make_pipeline(StandardScaler(), DecisionTreeRegressor(max_depth=best_depth, random_state=49))
    pipe_ad_std.fit(X_train, y_train)
    r2_ad_std = pipe_ad_std.score(X_test, y_test)

    # recherche du meilleur n_neighbors
    best_k = 4
    best_score_knn = -np.inf
    for k in [3, 4, 5, 7, 10, 15, 20]:
        knn_test = make_pipeline(StandardScaler(), KNeighborsRegressor(n_neighbors=k))
        knn_test.fit(X_train, y_train)
        score = knn_test.score(X_test, y_test)
        if score > best_score_knn:
            best_score_knn = score
            best_k = k
    print(f"  \nMeilleur k : {best_k} (R²={best_score_knn:.4f})")

    r2_knn = best_score_knn

    pipe_knn_norm = make_pipeline(MinMaxScaler(), KNeighborsRegressor(n_neighbors=best_k))
    pipe_knn_norm.fit(X_train, y_train)
    r2_knn_norm = pipe_knn_norm.score(X_test, y_test)

    pipe_knn_std = make_pipeline(StandardScaler(), KNeighborsRegressor(n_neighbors=best_k))
    pipe_knn_std.fit(X_train, y_train)
    r2_knn_std = best_score_knn

    best_lr = max(r2_lr, r2_lr_norm, r2_lr_std)
    best_ad = max(r2_ad, r2_ad_norm, r2_ad_std)
    best_knn = max(r2_knn, r2_knn_norm, r2_knn_std)

    resultats = pd.DataFrame({
        "Méthode": ["LR", "Normalisation + LR", "Standardisation + LR",
                    "AD", "Normalisation + AD", "Standardisation + AD",
                    "KNN", "Normalisation + KNN", "Standardisation + KNN",
                    "Best: LR", "Best: AD", "Best: KNN"],
        "r2": [r2_lr, r2_lr_norm, r2_lr_std,
               r2_ad, r2_ad_norm, r2_ad_std,
               r2_knn, r2_knn_norm, r2_knn_std,
               best_lr, best_ad, best_knn]
    })
    resultats["r2"] = resultats["r2"].map(lambda x: f"{x:.6f}")
    print(f"  \nTableau récapitulatif (AD: max_depth={best_depth}, KNN: k={best_k}) :")
    print(resultats)

    predictions = pipe_knn_std.predict(X_test)
    plt.figure(figsize=(8, 6))
    plt.scatter(y_test, predictions, alpha=0.6, label="Estimations")
    plt.plot([y_test.min(), y_test.max()], [y_test.min(), y_test.max()], 'r--', lw=2, label="Diagonale")
    plt.xlabel('y_test')
    plt.ylabel('estimation')
    plt.title('Question 24')
    plt.legend()
    os.makedirs(FIGURES_DIR, exist_ok=True)
    plt.savefig(os.path.join(FIGURES_DIR, "predictions_knn.png"), dpi=150, bbox_inches="tight")
    plt.close()

    # Standardisation puis PCA
    scaler_pca = StandardScaler()
    X_train_scaled = scaler_pca.fit_transform(X_train)
    X_test_scaled = scaler_pca.transform(X_test)

    pca = PCA(n_components=2)
    X_train_pca = pca.fit_transform(X_train_scaled)
    X_test_pca = pca.transform(X_test_scaled)

    knn_pca = KNeighborsRegressor(n_neighbors=best_k)
    knn_pca.fit(X_train_pca, y_train)
    r2_knn_pca = knn_pca.score(X_test_pca, y_test)
    print(f"\nScore R² de KNN avant PCA : {r2_knn:.4f}")
    print(f"Score R² de KNN après PCA (2 composantes) : {r2_knn_pca:.4f}")

    # Matrice de corrélation
    corr_matrix = annonces.corr(numeric_only=True)
    fig, ax = plt.subplots(figsize=(12, 10))
    cax = ax.matshow(corr_matrix, cmap="cividis", vmin=-1, vmax=1)
    fig.colorbar(cax)
    ax.set_xticks(np.arange(len(corr_matrix.columns)))
    ax.set_yticks(np.arange(len(corr_matrix.columns)))
    ax.set_xticklabels(corr_matrix.columns, rotation=90)
    ax.set_yticklabels(corr_matrix.columns)
    for (i, j), val in np.ndenumerate(corr_matrix):
        ax.text(j, i, f"{val:.2f}", ha='center', va='center', color="black")
    plt.title("Matrice de corrélation des attributs")
    plt.savefig(os.path.join(FIGURES_DIR, "matrice_correlation.png"), dpi=150, bbox_inches="tight")
    plt.close()

    correlations = corr_matrix["Prix"].abs().drop("Prix")
    most_corr_attr = correlations.idxmax()
    most_corr_value = correlations.max()
    print(f"\nL'attribut le plus corrélé avec le prix est : {most_corr_attr} avec une corrélation de {most_corr_value:.2f}")

    top5_features = correlations.sort_values(ascending=False).head(5).index.tolist()
    print("\nLes 5 attributs les plus corrélés avec le prix :")
    for i, col in enumerate(top5_features, 1):
        print(f"{i}. {col} - Corrélation : {correlations[col]:.2f}")

    X_top5 = annonces[top5_features]
    X_train_top5, X_test_top5, y_train_top5, y_test_top5 = train_test_split(X_top5, y, test_size=0.25, random_state=49)

    knn_top5 = KNeighborsRegressor(n_neighbors=best_k)
    knn_top5.fit(X_train_top5, y_train_top5)
    r2_knn_top5 = knn_top5.score(X_test_top5, y_test_top5)
    print(f"\nR² KNN avec les 5 attributs les plus corrélés : {r2_knn_top5:.4f}\n")

if __name__ == "__main__":
    scrape("https://www.immo-entre-particuliers.com/annonces/france-ile-de-france/vente/ta-offer")
    main()