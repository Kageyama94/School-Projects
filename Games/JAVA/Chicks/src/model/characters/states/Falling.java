package model.characters.states;

import model.characters.*;
import model.characters.tasks.Parachutist;

public class Falling implements CharacterState {
    private int gravity = GameConfig.GRAVITY;
    private static final int cell = GameConfig.CELL_SIZE;

    public void setGravity(int gravity) { this.gravity = gravity; }
    
    @Override
    public void update(GameCharacter character) {
        int fallDistance = character.getFallDistance();
        if (PhysicsHelper.hasSupport(character)) {
            if ((fallDistance / cell) >= GameConfig.MAX_FALL_CELLS && !(character.getTask() instanceof Parachutist)) {
                character.markForDeath(); return;
            }
            character.setState(new Walking());
            character.setFallDistance(0);
        } else {
            character.setY(character.getY() + gravity);
            character.setFallDistance(fallDistance + gravity);
        }
    }
}
