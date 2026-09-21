package model.characters.tasks;

import model.characters.GameCharacter;

public interface CharacterTask {
    TaskType getType();
    boolean update(GameCharacter character);
    default void onEnterState(GameCharacter character) {}
}
