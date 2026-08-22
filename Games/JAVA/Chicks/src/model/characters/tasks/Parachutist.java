package model.characters.tasks;

import model.characters.GameCharacter;
import model.characters.GameConfig;
import model.characters.PhysicsHelper;
import model.characters.states.*;

public class Parachutist implements CharacterTask {
    @Override
    public boolean update(GameCharacter character) {
        if (character.getState() instanceof Falling) {
            Falling falling = (Falling) character.getState();
            falling.setGravity(GameConfig.PARACHUTE_GRAVITY);
            if (PhysicsHelper.hasSupport(character)) {
                falling.setGravity(GameConfig.GRAVITY);
                character.setFallDistance(0);
                character.setState(new Walking());
                return true;
            }
        }
        return false;
    }

    @Override public TaskType getType() { return TaskType.PARACHUTISTE; }
}