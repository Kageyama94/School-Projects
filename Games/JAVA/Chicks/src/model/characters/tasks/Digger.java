package model.characters.tasks;

import model.characters.*;
import model.characters.states.Walking;

// Creuse vers le bas, pendant 5 pas
public class Digger implements CharacterTask {
    private int steps = 0;
    private static final int cell = GameConfig.CELL_SIZE;
    private boolean stateChecked = false;

    @Override
    public boolean update(GameCharacter character) {
        if (!stateChecked) {
            if (!(character.getState() instanceof Walking)) return true;
            stateChecked = true;
        }
        if (!(character.getX() % cell == 0 && character.getY() % cell == 0)) return false;
        if (steps < 5) {
            character.getObstacle().removeObstacle(character.getX() / cell, (character.getY() / cell) + 1);
            character.setY(character.getY() + GameConfig.GRAVITY);
            steps++;
            return false;
        }
        character.setState(new Walking());
        return true;
    }

    @Override public TaskType getType() { return TaskType.FOREUR; }
}
