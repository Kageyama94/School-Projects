package view;

import java.awt.*;
import java.util.EnumMap;
import java.util.Map;

import controller.GameController.ChangeSpeedError;

public final class ViewConfig {
    private ViewConfig() {}

    // === Couleurs terrain ===
    public static final Color GRASS = new Color(0, 100, 0);
    public static final Color DARK = Color.BLACK;
    public static final Color ROAD = Color.GRAY;
    public static final Color MARKER = Color.YELLOW;

    // === Couleurs texte ===
    public static final Color TEXT = Color.WHITE;
    public static final Color TEXT_ON_MAP = Color.BLACK;

    // === Médailles ===
    public static final Color GOLD = new Color(212, 175, 55);
    public static final Color SILVER = new Color(192, 192, 192);
    public static final Color BRONZE = new Color(205, 127, 50);

    // === Dimensions ===
    public static final Dimension GAME_SIZE = new Dimension(900, 450);
    public static final Dimension SETUP_LABEL_SIZE = new Dimension(100, 25);
    public static final int CELL_INSET = 5;
    public static final int BUTTON_GAP = 10;
    public static final int SETUP_ROW_STRUT = 5;

    // === Polices ===
    public static final float FONT_TITLE = 20f;
    public static final float FONT_HEADER = 18f;
    public static final float FONT_LABEL = 16f;
    public static final float CELL_FONT_RATIO = 0.6f;

    // === Timing ===
    public static final int ERROR_DIALOG_MS = 1000;

    // === Titres de fenêtres / dialogues ===
    public static final String TITLE_ERROR = "Erreur";

    // === Traductions des erreurs contrôleur ===
    private static final Map<ChangeSpeedError, String> CHANGE_SPEED_MESSAGES = new EnumMap<>(ChangeSpeedError.class);
    static {
        CHANGE_SPEED_MESSAGES.put(ChangeSpeedError.DAMAGED, "Voiture endommagée !");
        CHANGE_SPEED_MESSAGES.put(ChangeSpeedError.ALREADY_BOOSTED, "Déjà boostée !");
        CHANGE_SPEED_MESSAGES.put(ChangeSpeedError.ALREADY_STOPPED, "Déjà arrêtée !");
    }
 
    public static String message(ChangeSpeedError err) { return CHANGE_SPEED_MESSAGES.get(err); }
}