package model.characters.tasks;

import model.characters.*;
import model.characters.states.Walking;

// Devient un obstacle et BLOQUE
public class Blocker implements CharacterTask {
    private static final int cell = GameConfig.CELL_SIZE;
    
    @Override
    public boolean update(GameCharacter character) {
        boolean isAligned = character.getX() % cell == 0 && character.getY() % cell == 0;
        if (isAligned && character.getState() instanceof Walking) {
            character.getObstacle().addBlocker(character.getX() / cell, character.getY() / cell);
            character.setState(null);
            character.markForDeath();
            return true;
        }
        return false;
    }

    @Override public TaskType getType() { return TaskType.BLOQUEUR; }
}
