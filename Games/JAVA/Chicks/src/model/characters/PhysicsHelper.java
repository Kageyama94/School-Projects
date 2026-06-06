package model.characters;

public final class PhysicsHelper {
    private PhysicsHelper() {}

    public static int getFrontCell(GameCharacter c) {
        return (c.getDirection() > 0)
            ? (c.getX() + GameConfig.CELL_SIZE) / GameConfig.CELL_SIZE
            : (c.getX() - 1) / GameConfig.CELL_SIZE;
    }

    public static boolean hasSupport(GameCharacter c) {
        int cellY = (c.getY() + GameConfig.CELL_SIZE) / GameConfig.CELL_SIZE;
        return c.getObstacle().isCollision(c.getX() / GameConfig.CELL_SIZE, cellY)
            || c.getObstacle().isCollision((c.getX() + GameConfig.CELL_SIZE - 1) / GameConfig.CELL_SIZE, cellY);
    }

    public static boolean canJump(GameCharacter c) {
        int front = getFrontCell(c);
        return c.getObstacle().isCollision(front, c.getY() / GameConfig.CELL_SIZE)
            && !c.getObstacle().isCollision(front, (c.getY() - 1) / GameConfig.CELL_SIZE)
            && !c.getObstacle().isCollision(c.getX() / GameConfig.CELL_SIZE, (c.getY() - 1) / GameConfig.CELL_SIZE);
    }
}