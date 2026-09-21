package model.characters.tasks;

import model.characters.*;

// Explose et détruit les obstacles, 2 cases aux alentours, apres 3 pas
public class Bomber implements CharacterTask {
    private int steps = 0;
    private final CellStepCounter stepCounter = new CellStepCounter();
    private static final int cell = GameConfig.CELL_SIZE;

    @Override
    public boolean update(GameCharacter character) {
        if (stepCounter.crossedCell(character)) steps++;
        if (steps >= 3) {
            destroyNearbyObstacles(character);
            character.markForDeath();
            return true;
        }
        return false;
    }

    @Override public void onEnterState(GameCharacter character) { stepCounter.reset(character); }
    @Override public TaskType getType() { return TaskType.BOMBEUR; }

    /**    x
     *   x x x
     * x x P x x
     *   x x x
     *     x
     */
    private void destroyNearbyObstacles(GameCharacter character) {
        int x = character.getX() / cell;
        int y = character.getY() / cell;

        int[][] positionsToDestroy = {
            {0, -2},
            {-1, -1}, {0, -1}, {1, -1},
            {-2, 0}, {-1, 0}, {0, 0}, {1, 0}, {2, 0}, 
            {-1, 1}, {0, 1}, {1, 1},
            {0, 2}
        };

        for (int[] pos : positionsToDestroy) character.getObstacle().removeObstacle(x + pos[0], y + pos[1]);
    }
}
