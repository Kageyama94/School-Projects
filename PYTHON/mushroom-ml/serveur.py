import joblib
import pandas as pd
from flask import Flask, request, redirect

app = Flask(__name__)

modeles = {
    "svm": joblib.load("modele/modele_svm.joblib"),
    "arbre": joblib.load("modele/modele_arbre.joblib"),
}
scaler = joblib.load("modele/scaler.joblib")

COLONNES = modeles["arbre"].feature_names_in_

LABELS = {0: "Comestible (E)", 1: "Inedible (I)", 2: "Poisonous (P)"}

@app.route("/")
def index():
    return redirect("/form")

@app.route("/form", methods=["GET"])
def form():
    with open("formulaire.html", encoding="utf-8") as f:
        return f.read()

@app.route("/rep", methods=["POST"])
def rep():
    choix = request.form["modele"]
    modele = modeles[choix]

    entree = pd.DataFrame([[0] * len(COLONNES)], columns=COLONNES)
    for champ in request.form:
        if champ in COLONNES:
            entree[champ] = float(request.form[champ])

    donnees = scaler.transform(entree) if choix == "svm" else entree
    prediction = modele.predict(donnees)[0]

    return f"<h1>Résultat : {LABELS.get(prediction, 'Inconnu')}</h1><a href='/form'>Retour</a>"

if __name__ == "__main__":
    app.run(port=8080)