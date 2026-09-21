package controller;

import javax.swing.*;
import java.util.ArrayList;
import java.util.List;

import model.GameState;
import model.GameState.CharacterSnapshot;
import model.characters.*;
import model.characters.tasks.TaskType;
import model.environment.*;
import view.GameView;

public class GameController implements CharacterObserver {
    private final GameView view;
    private final TaskController taskController;
    private final Obstacle obstacle = new Obstacle();

    private final ArrayList<GameCharacter> characters = new ArrayList<>();

    private int characterIndex = 0;
    private int charactersAtExit = 0;
    private int deadCharacters = 0;
    private boolean gameEnded = false;
    private boolean win = false;

    private Timer gameTimer;
    private Timer appearanceTimer;

    public GameController(GameView view, JFrame frame) {
        this.view = view;
        this.taskController = new TaskController(characters);
        initModel(frame.getContentPane().getWidth(), frame.getContentPane().getHeight());
        view.setObstacle(obstacle);
        view.setPortal(portal);
    }

    public void start() {
        startGameLoop();
        startAppearanceTimer();
    }

    private final Portal portal = new Portal(
        3 * GameConfig.CELL_SIZE, 3 * GameConfig.CELL_SIZE, // entrée
        34 * GameConfig.CELL_SIZE, 9 * GameConfig.CELL_SIZE // sortie
    );

    private void initModel(int frameWidth, int frameHeight) {
        obstacle.buildLevel(frameWidth / GameConfig.CELL_SIZE, frameHeight / GameConfig.CELL_SIZE);
        for (int i = 0; i < GameConfig.TOTAL_CHARACTERS; i++) {
            GameCharacter c = new GameCharacter(portal.getEntryX(), portal.getEntryY(), obstacle, portal, frameWidth);
            c.setObserver(this);
            characters.add(c);
        }
    }

    private void startGameLoop() {
        gameTimer = new Timer(GameConfig.GAME_TICK_MS, e -> {
            characters.forEach(GameCharacter::update);
            view.render(buildState());
        });
        gameTimer.start();
    }

    private void startAppearanceTimer() {
        appearanceTimer = new Timer(GameConfig.APPEARANCE_DELAY_MS, e -> {
            if (characterIndex >= characters.size()) { appearanceTimer.stop(); return; }
            characters.get(characterIndex).setVisible(true);
            characterIndex++;
        });
        appearanceTimer.start();
    }

    private void endGame(boolean playerWon) {
        if (gameEnded) return;
        gameEnded = true;
        win = playerWon;
        gameTimer.stop();
        appearanceTimer.stop();
        view.onGameEnd();
        view.render(buildState());
    }

    @Override
    public void onCharacterEnd(boolean hasReachedExit, boolean isDead) {
        if (hasReachedExit) charactersAtExit++;
        else if (isDead) deadCharacters++;
        view.render(buildState());
        if (visibleCount() == 0 && characterIndex >= GameConfig.TOTAL_CHARACTERS) endGame(charactersAtExit > 0);
    }

    public void onMouseMoved(int row, int col) { view.setActiveCell(row, col); }
    public void onMouseExited() { view.setActiveCell(-1, -1); }
    public void onMouseClicked(int row, int col, TaskType selectedTask) {
        if (gameEnded || selectedTask == null) return;
        taskController.applyTask(row, col, selectedTask);
        view.render(buildState());
    }

    private long visibleCount() { return characters.stream().filter(GameCharacter::isVisible).count(); }

    private GameState buildState() {
        List<CharacterSnapshot> snapshots = characters.stream()
            .filter(GameCharacter::isVisible)
            .map(c -> new CharacterSnapshot(c.getX(), c.getY(), c.getTask() != null ? c.getTask().getType() : null))
            .toList();
        return new GameState(
            GameConfig.TOTAL_CHARACTERS, (int) visibleCount(),
            charactersAtExit, deadCharacters,
            gameEnded, win, snapshots);
    }
}