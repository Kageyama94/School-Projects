package model.characters.states;

import model.characters.*;

public class Jumping implements CharacterState {
    private static final int JUMP = GameConfig.JUMP_SPEED;
    private static final int cell = GameConfig.CELL_SIZE;

    @Override
    public void update(GameCharacter character) {
        int jumpDistance = character.getJumpDistance();
        if (PhysicsHelper.hasSupport(character)) {
            if (!PhysicsHelper.canJump(character)) {
                character.setState(new Walking());
                character.setJumpDistance(0);
                return;
            }
            jumpDistance = 0;
        }
        if (jumpDistance < cell) {
            character.setY(character.getY() - JUMP);
            character.setJumpDistance(jumpDistance + JUMP);
        } else {
            character.setX(character.getX() + character.getDirection());
            character.setJumpDistance(0);
            character.setState(new Walking());
        }
    }
}