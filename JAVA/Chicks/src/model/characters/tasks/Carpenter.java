package model.characters.tasks;

import model.characters.*;

// Escalier de 5 marches dans le sens de marche.
// S'arrête au rebord, sur un obstacle, ou si le perso fait demi-tour.
public class Carpenter implements CharacterTask {
    private int steps = 0;
    private static final int cell = GameConfig.CELL_SIZE;
    private final CellStepCounter stepCounter = new CellStepCounter();
    private int lastObstacleY;
    private int buildDir;

    @Override
    public boolean update(GameCharacter character) {
        if (!PhysicsHelper.isAlignedX(character)) return false;

        // Demi-tour (rebord atteint, blocker devant...) → on arrête
        int curDir = PhysicsHelper.directionSign(character);
        if (curDir != buildDir) return true;

        if (stepCounter.crossedCell(character)) {
            int frontCellX = character.getX() / cell + buildDir;
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
        stepCounter.reset(character);
        this.lastObstacleY = character.getY();
        this.buildDir = PhysicsHelper.directionSign(character);
    }

    @Override public TaskType getType() { return TaskType.CHARPENTIER; }
}