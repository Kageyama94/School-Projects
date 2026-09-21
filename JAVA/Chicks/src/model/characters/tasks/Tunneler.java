package model.characters.tasks;

import model.characters.*;

public class Tunneler implements CharacterTask {
    @Override
    public boolean update(GameCharacter character) {
        int currentCellX = PhysicsHelper.getFrontCell(character);
        int currentCellY = character.getY() / GameConfig.CELL_SIZE;
        if (character.getObstacle().isCollision(currentCellX, currentCellY)) {
            character.getObstacle().removeObstacle(currentCellX, currentCellY);
            int nextCellX = currentCellX + PhysicsHelper.directionSign(character);
            if (!character.getObstacle().isCollision(nextCellX, currentCellY)) return true;
        }
        return false;
    }

    @Override public TaskType getType() { return TaskType.TUNNELIER; }
}
