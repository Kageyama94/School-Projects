package controller;

import java.awt.Rectangle;
import java.util.ArrayList;

import model.characters.*;
import model.characters.tasks.*;

public class TaskController {
    private static final int CELL = GameConfig.CELL_SIZE;
    private final ArrayList<GameCharacter> characters;

    public TaskController(ArrayList<GameCharacter> characters) { this.characters = characters; }

    private static CharacterTask createTask(TaskType type) {
        return switch (type) {
            case BLOQUEUR -> new Blocker();
            case BOMBEUR -> new Bomber();
            case CHARPENTIER -> new Carpenter();
            case GRIMPEUR -> new Climber();
            case FOREUR -> new Digger();
            case PARACHUTISTE -> new Parachutist();
            case TUNNELIER -> new Tunneler();
        };
    }

    public void applyTask(int row, int col, TaskType taskType) {
        CharacterTask task = createTask(taskType);

        Rectangle cellRect = new Rectangle(col * CELL, row * CELL, CELL, CELL);
        for (GameCharacter character : characters) {
            if (!character.isVisible()) continue;
            Rectangle charRect = new Rectangle(character.getX(), character.getY(), CELL, CELL);
            if (cellRect.intersects(charRect)) { character.setTask(task); break; }
        }
    }
}
