package model.characters;

public final class GameConfig {
    private GameConfig() {}
    public static final int CELL_SIZE = 30;
    public static final int GRAVITY = 2;
    public static final int PARACHUTE_GRAVITY = 1;
    public static final int MAX_FALL_CELLS = 5;
    public static final int JUMP_SPEED = 2;
    public static final int WALK_SPEED = 2;
    public static final int CLIMB_SPEED = 2;
    public static final int APPEARANCE_DELAY_MS = 1000;
    public static final int TOTAL_CHARACTERS = 100;
    public static final int GAME_TICK_MS = 10;
}
