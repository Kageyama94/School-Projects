package model.environment;

import java.awt.*;

import model.characters.GameConfig;

public class Portal {
    private static final int cell = GameConfig.CELL_SIZE;
    private final int entryX, entryY;
    private final int exitX, exitY;

    public Portal(int entryX, int entryY, int exitX, int exitY) {
        this.entryX = entryX;
        this.entryY = entryY;
        this.exitX = exitX;
        this.exitY = exitY;
    }

    public int getEntryX() { return entryX; }
    public int getEntryY() { return entryY; }

    public void draw(Graphics g) {
        drawPortal(g, entryX, entryY, Color.WHITE, true);
        drawPortal(g, exitX, exitY, Color.GREEN, false);
    }

    private void drawPortal(Graphics g, int x, int y, Color color, boolean pointsUp) {
        g.setColor(color);
        int tip = pointsUp ? y + cell : y;
        int base = pointsUp ? y : y + cell;
        int[] xPoints = { x + cell / 2, x, x + cell };
        int[] yPoints = { tip, base, base };
        g.fillPolygon(xPoints, yPoints, 3);
    }

    public boolean isAtExit(int x, int y) { return x == exitX / cell && y == exitY / cell; }
}