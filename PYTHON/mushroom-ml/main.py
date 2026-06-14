import requests
import csv
import pandas as pd
import joblib
import matplotlib.pyplot as plt
import os
from bs4 import BeautifulSoup
from concurrent.futures import ThreadPoolExecutor
from sklearn.model_selection import train_test_split
from sklearn.svm import SVC
from sklearn.tree import DecisionTreeClassifier, plot_tree
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import accuracy_score, confusion_matrix

# ============================================================
# Partie 1 : Récupération des données
# ============================================================

for d in ["data", "figures", "modele"]:
    os.makedirs(d, exist_ok=True)

def comestible(soup):
    cat_div = soup.find('div', class_='catlink')
    if cat_div is None:
        return ''
    category = cat_div.find('a').text.strip()
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
    response = requests.get(url, timeout=10)
    if response.status_code != 200:
        return None
    soup = BeautifulSoup(response.content, 'html.parser')
    type_ = comestible(soup)
    color_info = color(soup)
    shape_info = shape(soup)
    surface_info = surface(soup)
    return f"{type_},{color_info},{shape_info},{surface_info}"

def extract_mushroom_links(url):
    response = requests.get(url)
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
                    writer.writerow(info.split(','))
                print(f"{done}/{len(urls)}", end='\r')
    print()

if __name__ == "__main__":
    url_all = "https://ultimate-mushroom.com/mushroom-alphabet.html"
    mushroom_links = list(extract_mushroom_links(url_all))
    print(f"Nombre de liens trouvés: {len(mushroom_links)}")
    write_to_csv('data/champignons.csv', mushroom_links, max_workers=8)

    # ============================================================
    # Partie 2 : Manipulation des données
    # ============================================================
 
    champignons = pd.read_csv('data/champignons.csv')
 
    print(champignons["Edible"].value_counts(dropna=False))
 
    champignons["Edible"] = champignons["Edible"].replace({"E": 0, "I": 1, "P": 2})
 
    champignons["Edible"] = champignons["Edible"].fillna(-1).astype(int)
    print(champignons["Edible"].value_counts())
 
    for col in ["Shape", "Surface"]:
        valeurs = pd.unique(champignons[col].str.split("-").explode().dropna())
        for v in valeurs:
            champignons[f"{col}_{v}"] = champignons[col].str.contains(v, regex=False, na=False).astype(int)
    champignons = champignons.drop(columns=["Shape", "Surface"])
    print("Taille après indicatrices:", champignons.shape)
 
    couleurs = pd.unique(champignons["Color"].str.split("-").explode().dropna())
    print(len(couleurs), sorted(couleurs))

    rgb_map = {
        "Black":  [0, 0, 0],       "Blue":   [0, 0, 255],
        "Brown":  [139, 69, 19],   "Gray":   [128, 128, 128],
        "Green":  [0, 128, 0],     "Lilac":  [200, 162, 200],
        "Orange": [255, 165, 0],   "Pale":   [240, 240, 230],
        "Pink":   [255, 192, 203], "Purple": [128, 0, 128],
        "Red":    [255, 0, 0],     "Tan":    [210, 180, 140],
        "Violet": [238, 130, 238], "White":  [255, 255, 255],
        "Yellow": [255, 255, 0],
    }
    colors = pd.DataFrame({"Color": champignons["Color"].dropna().unique()})
 
    def moyenne_rgb(combinaison):
        comps = [rgb_map[c] for c in combinaison.split("-") if c in rgb_map]
        moy = [sum(canal) / len(canal) for canal in zip(*comps)]
        return pd.Series(moy)
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
        X, y, test_size=0.25, random_state=42)

    scaler = StandardScaler()
    X_train_scaled = scaler.fit_transform(X_train)
    X_test_scaled = scaler.transform(X_test)

    svc_scaled = SVC()
    svc_scaled.fit(X_train_scaled, y_train)
    y_pred_scaled = svc_scaled.predict(X_test_scaled)
    print("\n[SVC + scaler] accuracy:", accuracy_score(y_test, y_pred_scaled))
    print("[SVC + scaler] matrice de confusion:\n", confusion_matrix(y_test, y_pred_scaled))

    tree = DecisionTreeClassifier(max_depth=3, random_state=42)
    tree.fit(X_train, y_train)
    print("\n[Arbre] accuracy:", accuracy_score(y_test, tree.predict(X_test)))

    plt.figure(figsize=(20, 10))
    plot_tree(tree,
              feature_names=X.columns,
              class_names=["E", "I", "P"],
              filled=True, rounded=True, fontsize=8)
    plt.savefig("figures/arbre_decision.png", dpi=150, bbox_inches="tight")
    plt.close()
    print("\nArbre sauvegardé dans figures/arbre_decision.png")

    joblib.dump(svc_scaled, "modele/modele_svm.joblib")
    joblib.dump(scaler, "modele/scaler.joblib")
    joblib.dump(tree, "modele/modele_arbre.joblib")
    print("Modèles sauvegardés.")