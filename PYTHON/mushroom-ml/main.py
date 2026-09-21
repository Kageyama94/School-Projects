import requests
import csv
import pandas as pd
import joblib
import matplotlib.pyplot as plt
import os
import threading
from bs4 import BeautifulSoup
from concurrent.futures import ThreadPoolExecutor
from sklearn.model_selection import train_test_split, GridSearchCV
from sklearn.svm import SVC
from sklearn.tree import DecisionTreeClassifier, plot_tree
from sklearn.preprocessing import StandardScaler
from sklearn.pipeline import Pipeline
from sklearn.metrics import accuracy_score, confusion_matrix, classification_report

from couleurs import moyenne_rgb

# ============================================================
# Partie 1 : Récupération des données
# ============================================================

for d in ["data", "figures", "modele"]:
    os.makedirs(d, exist_ok=True)

_local = threading.local()

def get_session():
    if not hasattr(_local, "session"):
        _local.session = requests.Session()
        _local.session.headers.update({"User-Agent": "Mozilla/5.0 (compatible; mushroom-ml-scraper/1.0)"})
    return _local.session

def comestible(soup):
    cat_div = soup.find('div', class_='catlink')
    if cat_div is None:
        return ''
    link = cat_div.find('a')
    if link is None:
        return ''
    category = link.text.strip()
    if category == 'Poisonous Mushrooms':
        return 'P'
    elif category == 'Edible Mushrooms':
        return 'E'
    elif category == 'Inedible Mushrooms':
        return 'I'
    else:
        return ''
    
def _champ(soup, label):
    """Récupère les valeurs <a> du <p> contenant <strong>label</strong>."""
    mprofile_div = soup.find('div', class_='mprofile')
    if not mprofile_div:
        return []
    strong = mprofile_div.find('strong', string=label)
    if not strong:
        return []
    p = strong.find_parent('p')
    return [a.text.strip() for a in p.find_all('a')]

def color(soup):
    return '-'.join(_champ(soup, 'Color:'))

def shape(soup):
    return '-'.join(v.replace(' ', '').replace('-', '') for v in _champ(soup, 'Shape:'))

def surface(soup):
    return '-'.join(v.replace(' ', '').replace('-', '') for v in _champ(soup, 'Surface:'))

def csv_info(url):
    try:
        response = get_session().get(url, timeout=10)
        if response.status_code != 200:
            return None
        soup = BeautifulSoup(response.content, 'html.parser')
        type_ = comestible(soup)
        color_info = color(soup)
        shape_info = shape(soup)
        surface_info = surface(soup)
        return (type_, color_info, shape_info, surface_info)
    except (requests.exceptions.RequestException, AttributeError):
        return None

def extract_mushroom_links(url):
    try:
        response = get_session().get(url, timeout=10)
    except requests.exceptions.RequestException:
        return
    if response.status_code != 200:
        return
    soup = BeautifulSoup(response.content, 'html.parser')
    links_seen = set()
    for link in soup.find_all('a', href=True):
        href = link['href']
        if href.startswith('#'):
            continue
        if href.startswith('/'):
            href = 'https://ultimate-mushroom.com' + href
        if not href.startswith('http'):
            continue
        if any(seg in href for seg in ('/poisonous/', '/edible/', '/inedible/')) and href.endswith('.html'):
            if href not in links_seen:
                links_seen.add(href)
                yield href

def write_to_csv(filename, urls, max_workers=20):
    with open(filename, mode='w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file)
        writer.writerow(["Edible", "Color", "Shape", "Surface"])
        done = 0
        with ThreadPoolExecutor(max_workers=max_workers) as executor:
            for info in executor.map(csv_info, urls):
                done += 1
                if info is not None:
                    writer.writerow(info)
                print(f"{done}/{len(urls)}", end='\r')
    print()

if __name__ == "__main__":
    csv_path = 'data/champignons.csv'
    if os.path.exists(csv_path):
        print(f"{csv_path} existe déjà, scraping ignoré (supprimez le fichier pour le régénérer).")
    else:
        url_all = "https://ultimate-mushroom.com/mushroom-alphabet.html"
        mushroom_links = list(extract_mushroom_links(url_all))
        print(f"Nombre de liens trouvés: {len(mushroom_links)}")
        write_to_csv(csv_path, mushroom_links, max_workers=8)

    # ============================================================
    # Partie 2 : Manipulation des données
    # ============================================================
 
    champignons = pd.read_csv(csv_path)

    print(champignons["Edible"].value_counts(dropna=False))

    n_avant = len(champignons)
    champignons = champignons.dropna(subset=["Edible"])
    if len(champignons) < n_avant:
        print(f"{n_avant - len(champignons)} ligne(s) non classée(s) supprimée(s).")

    champignons["Edible"] = champignons["Edible"].map({"E": 0, "I": 1, "P": 2}).astype(int)
    print(champignons["Edible"].value_counts())
 
    for col in ["Shape", "Surface"]:
        dummies = champignons[col].str.get_dummies(sep="-").add_prefix(f"{col}_")
        champignons = pd.concat([champignons, dummies], axis=1)
    champignons = champignons.drop(columns=["Shape", "Surface"])
    print("Taille après indicatrices:", champignons.shape)
 
    couleurs = pd.unique(champignons["Color"].str.split("-").explode().dropna())
    print(len(couleurs), sorted(couleurs))

    colors = pd.DataFrame({"Color": champignons["Color"].dropna().unique()})
    colors[["R", "G", "B"]] = colors["Color"].apply(moyenne_rgb)
    print(colors.head())
    champignons = champignons.merge(colors, on="Color", how="left").drop(columns=["Color"])
    champignons[["R", "G", "B"]] = champignons[["R", "G", "B"]].fillna(-255)
 
    print("Taille finale:", champignons.shape)
    print("NA restants:", champignons.isna().sum().sum())

    # ============================================================
    # Partie 3 : Apprentissage
    # ============================================================

    X = champignons.drop(columns=["Edible"])
    y = champignons["Edible"]
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.25, random_state=42, stratify=y)

    noms_classes = ["E", "I", "P"]

    pipeline_svc = Pipeline([("scaler", StandardScaler()), ("svc", SVC(class_weight="balanced"))])
    grid_svc = GridSearchCV(
        pipeline_svc,
        [
            {"svc__kernel": ["rbf"], "svc__C": [0.1, 1, 10], "svc__gamma": ["scale", "auto"]},
            {"svc__kernel": ["linear"], "svc__C": [0.1, 1, 10]},
        ],
        cv=5, scoring="recall_macro")
    grid_svc.fit(X_train, y_train)
    svc_scaled = grid_svc.best_estimator_
    y_pred_scaled = svc_scaled.predict(X_test)
    print("\n[SVC + scaler] meilleurs paramètres:", grid_svc.best_params_)
    print("[SVC + scaler] accuracy:", accuracy_score(y_test, y_pred_scaled))
    print("[SVC + scaler] matrice de confusion:\n", confusion_matrix(y_test, y_pred_scaled, labels=[0, 1, 2]))
    print("[SVC + scaler] rapport par classe:\n",
          classification_report(y_test, y_pred_scaled, labels=[0, 1, 2], target_names=noms_classes))

    grid_tree = GridSearchCV(
        DecisionTreeClassifier(random_state=42, class_weight="balanced"),
        {"max_depth": [2, 3, 4, 5, 6]},
        cv=5, scoring="recall_macro")
    grid_tree.fit(X_train, y_train)
    tree = grid_tree.best_estimator_
    y_pred_tree = tree.predict(X_test)
    print("\n[Arbre] meilleure profondeur:", grid_tree.best_params_)
    print("[Arbre] accuracy:", accuracy_score(y_test, y_pred_tree))
    print("[Arbre] matrice de confusion:\n", confusion_matrix(y_test, y_pred_tree, labels=[0, 1, 2]))
    print("[Arbre] rapport par classe:\n",
          classification_report(y_test, y_pred_tree, labels=[0, 1, 2], target_names=noms_classes))

    plt.figure(figsize=(20, 10))
    plot_tree(tree,
              max_depth=3,
              feature_names=X.columns,
              class_names=noms_classes,
              filled=True, rounded=True, fontsize=8)
    plt.savefig("figures/arbre_decision.png", dpi=150, bbox_inches="tight")
    plt.close()
    print(f"\nArbre (profondeur réelle {tree.get_depth()}, 3 premiers niveaux affichés) "
          "sauvegardé dans figures/arbre_decision.png")

    joblib.dump(svc_scaled, "modele/modele_svm.joblib")
    joblib.dump(tree, "modele/modele_arbre.joblib")
    print("Modèles sauvegardés.")