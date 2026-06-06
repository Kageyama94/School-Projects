package model.characters.states;

import model.characters.*;

public class Walking implements CharacterState {
    private static final int cell = GameConfig.CELL_SIZE;

    @Override
    public void update(GameCharacter character) {
        int direction = character.getDirection();
        int nextX = character.getX() + direction;
        if (isAtBoundary(character, nextX, direction) || isBlockerAhead(character)) character.setDirection(direction * -1);
        else if (isFalling(character)) character.setState(new Falling());
        else if (PhysicsHelper.canJump(character) && character.getX() % cell == 0) character.setState(new Jumping());
        else if (isCollision(character)) character.setDirection(direction * -1);
        else character.setX(character.getX() + direction);
    }

    private boolean isAtBoundary(GameCharacter character, int nextX, int direction) {
        int frameWidth = character.getFrameWidth();
        return direction > 0 && (nextX + cell) >= frameWidth || (direction < 0 && nextX <= 0);
    }

    private boolean isBlockerAhead(GameCharacter character) { return character.getObstacle().isBlockerAt(PhysicsHelper.getFrontCell(character), character.getY() / cell); }
    private boolean isFalling(GameCharacter character) { return !PhysicsHelper.hasSupport(character); }
    private boolean isCollision(GameCharacter character) { return character.getObstacle().isCollision(PhysicsHelper.getFrontCell(character), character.getY() / cell); }
}
