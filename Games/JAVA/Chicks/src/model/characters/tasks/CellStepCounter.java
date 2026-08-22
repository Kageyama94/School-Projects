package model.characters.tasks;

import model.characters.GameCharacter;
import model.characters.GameConfig;

final class CellStepCounter {
    private int lastCellX;

    void reset(GameCharacter character) { lastCellX = character.getX() / GameConfig.CELL_SIZE; }

    boolean crossedCell(GameCharacter character) {
        int currentCellX = character.getX() / GameConfig.CELL_SIZE;
        if (currentCellX == lastCellX) return false;
        lastCellX = currentCellX;
        return true;
    }
}
