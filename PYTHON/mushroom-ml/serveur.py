import joblib
import pandas as pd
from flask import Flask, request, redirect, render_template

from couleurs import RGB_MAP, moyenne_rgb

app = Flask(__name__)

modeles = {
    "svm": joblib.load("modele/modele_svm.joblib"),
    "arbre": joblib.load("modele/modele_arbre.joblib"),
}

COLONNES = modeles["arbre"].feature_names_in_

LABELS = {0: "Comestible (E)", 1: "Inedible (I)", 2: "Poisonous (P)"}

def colonnes_par_prefixe(prefixe):
    return sorted(c[len(prefixe):] for c in COLONNES if c.startswith(prefixe))

FORMES = colonnes_par_prefixe("Shape_")
SURFACES = colonnes_par_prefixe("Surface_")
COULEURS = sorted(RGB_MAP)

@app.route("/")
def index():
    return redirect("/form")

@app.route("/form", methods=["GET"])
def form():
    return render_template("formulaire.html", formes=FORMES, surfaces=SURFACES, couleurs=COULEURS)

@app.route("/rep", methods=["POST"])
def rep():
    choix = request.form.get("modele")
    if choix not in modeles:
        return "<h1>Erreur : modèle inconnu.</h1><a href='/form'>Retour</a>", 400
    modele = modeles[choix]

    entree = pd.DataFrame([[0] * len(COLONNES)], columns=COLONNES)
    for champ in request.form:
        if champ in COLONNES:
            try:
                entree[champ] = float(request.form[champ])
            except ValueError:
                return f"<h1>Erreur : valeur invalide pour {champ}.</h1><a href='/form'>Retour</a>", 400

    rgb = moyenne_rgb("-".join(request.form.getlist("Color")))
    entree[["R", "G", "B"]] = rgb.values if len(rgb) == 3 else [-255, -255, -255]

    prediction = modele.predict(entree)[0]

    return f"<h1>Résultat : {LABELS.get(prediction, 'Inconnu')}</h1><a href='/form'>Retour</a>"

if __name__ == "__main__":
    app.run(port=8080)