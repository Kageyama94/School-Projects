package model.characters;

import model.characters.states.*;
import model.characters.tasks.CharacterTask;
import model.environment.*;

public class GameCharacter {
    private int x, y;
    private final Obstacle obstacle;
    private static final int cell = GameConfig.CELL_SIZE;
    private CharacterState state;
    private CharacterTask task;
    private boolean isVisible = false;
    private int direction = GameConfig.WALK_SPEED;
    private int fallDistance, jumpDistance;
    private CharacterObserver observer;
    private boolean isDone = false;
    private boolean shouldDie = false;
    private final Portal portal;
    private final int frameWidth;

    public GameCharacter(int x, int y, Obstacle obstacle, Portal portal, int frameWidth) {
        this.x = x;
        this.y = y;
        this.obstacle = obstacle;
        this.portal = portal;
        this.frameWidth = frameWidth;
    }

    public void setObserver(CharacterObserver observer) { this.observer = observer; }
    public void notifyObserver(boolean hasReachedExit, boolean isDead) {
        if (isDone) return;
        isDone = true;
        if (observer != null) observer.onCharacterEnd(hasReachedExit, isDead);
    }

    public boolean isVisible() { return isVisible; }
    public void setVisible(boolean visible) {
        this.isVisible = visible;
        if (isVisible) setState(new Falling());
    }

    public void setState(CharacterState state) { this.state = state; }
    public CharacterState getState() { return state; }

    public void setTask(CharacterTask task) {
        this.task = task;
        if (task != null) task.onEnterState(this);
    }
    public CharacterTask getTask() { return task; }

    public void update() {
        if (!isVisible) return;
        if (task != null) {
            boolean done = task.update(this);
            if (done) setTask(null);
        }
        if (state != null) state.update(this);
        checkEndCondition();
    }

    public void markForDeath() { this.shouldDie = true; }

    private void checkEndCondition() {
        boolean atLava = obstacle.isAtLava(x / cell, y / cell);
        boolean atExit = portal.isAtExit(x / cell, y / cell);
        if (atLava || atExit || shouldDie) {
            setVisible(false);
            notifyObserver(atExit, atLava || shouldDie);
        }
    }

    public int getX() { return x; }
    public void setX(int x) { this.x = x; }
    public int getY() { return y; }
    public void setY(int y) { this.y = y; }
    public Obstacle getObstacle() { return obstacle; }
    public int getDirection() { return direction; }
    public void setDirection(int direction) { this.direction = direction; }
    public int getJumpDistance() { return jumpDistance; }
    public void setJumpDistance(int jumpDistance) { this.jumpDistance = jumpDistance; }
    public int getFallDistance() { return fallDistance; }
    public void setFallDistance(int fallDistance) { this.fallDistance = fallDistance; }
    public int getFrameWidth() { return frameWidth; }
}