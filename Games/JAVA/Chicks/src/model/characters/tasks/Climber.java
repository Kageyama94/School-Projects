package model.characters.tasks;

import model.characters.*;
import model.characters.states.*;

// Grimpe le mur qu'il rencontre, puis reprend la marche au sommet.
public class Climber implements CharacterTask {
    private static final int cell = GameConfig.CELL_SIZE;
    private boolean climbing = false;

    @Override
    public boolean update(GameCharacter character) {
        int frontX = PhysicsHelper.getFrontCell(character);
        int currentY = character.getY() / cell;
        boolean wallAhead = character.getObstacle().isCollision(frontX, currentY);

        if (!climbing) {
            if (wallAhead && PhysicsHelper.isAlignedX(character)) {
                climbing = true;
                character.setState(null);
            } else return false;
        }

        if (character.getObstacle().isCollision(character.getX() / cell, currentY) || isAtBoundary(character.getY())) {
            character.setState(new Falling()); return true;
        }
        if (wallAhead) { climb(character); return false; }
        if (!PhysicsHelper.isAlignedY(character)) { climb(character); return false; }
        character.setX(character.getX() + character.getDirection());
        character.setState(new Walking());
        return true;
    }
    
    @Override public TaskType getType() { return TaskType.GRIMPEUR; }

    private boolean isAtBoundary(int currentY) { return currentY <= 0; }
    private void climb(GameCharacter character) { character.setY(character.getY() - GameConfig.CLIMB_SPEED); }
}
