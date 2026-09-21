import pandas as pd

# Palette partagée entre l'entraînement (main.py) et le formulaire web (serveur.py),
# pour que les couleurs proposées dans le formulaire soient toujours celles vues à l'entraînement.
RGB_MAP = {
    "Black":  [0, 0, 0],       "Blue":   [0, 0, 255],
    "Brown":  [139, 69, 19],   "Gray":   [128, 128, 128],
    "Green":  [0, 128, 0],     "Lilac":  [200, 162, 200],
    "Orange": [255, 165, 0],   "Pale":   [240, 240, 230],
    "Pink":   [255, 192, 203], "Purple": [128, 0, 128],
    "Red":    [255, 0, 0],     "Tan":    [210, 180, 140],
    "Violet": [238, 130, 238], "White":  [255, 255, 255],
    "Yellow": [255, 255, 0],
}

def moyenne_rgb(combinaison):
    """Moyenne RGB d'une combinaison de couleurs jointes par '-' (ex: 'White-Yellow')."""
    noms = combinaison.split("-")
    inconnues = [c for c in noms if c and c not in RGB_MAP]
    if inconnues:
        print(f"Couleur(s) inconnue(s) ignorée(s) (absente de RGB_MAP): {inconnues}")
    comps = [RGB_MAP[c] for c in noms if c in RGB_MAP]
    return pd.Series([sum(canal) / len(canal) for canal in zip(*comps)])
