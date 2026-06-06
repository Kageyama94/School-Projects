package model.characters.tasks;

import model.characters.*;

// Escalier de 5 marches dans le sens de marche.
// S'arrête au rebord, sur un obstacle, ou si le perso fait demi-tour.
public class Carpenter implements CharacterTask {
    private int steps = 0;
    private static final int cell = GameConfig.CELL_SIZE;
    private int lastCellX, lastObstacleY;
    private int buildDir;

    @Override
    public boolean update(GameCharacter character) {
        if (character.getX() % cell != 0) return false;

        // Demi-tour (rebord atteint, blocker devant...) → on arrête
        int curDir = (character.getDirection() > 0) ? 1 : -1;
        if (curDir != buildDir) return true;

        int currentCellX = character.getX() / cell;
        if (lastCellX != currentCellX) {
            lastCellX = currentCellX;
            int frontCellX = currentCellX + buildDir;
            int targetY = lastObstacleY / cell;

            // Stop si bordure ou obstacle déjà présent
            if (frontCellX < 0 || targetY < 0
                    || frontCellX >= character.getFrameWidth() / cell
                    || character.getObstacle().isCollision(frontCellX, targetY)) {
                return true;
            }

            character.getObstacle().addObstacle(frontCellX, targetY);
            lastObstacleY -= cell;
            steps++;
        }
        return steps >= 5;
    }

    @Override
    public void onEnterState(GameCharacter character) {
        this.lastCellX = character.getX() / cell;
        this.lastObstacleY = character.getY();
        this.buildDir = (character.getDirection() > 0) ? 1 : -1;
    }

    @Override public TaskType getType() { return TaskType.CHARPENTIER; }
}