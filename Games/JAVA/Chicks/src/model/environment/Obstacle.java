package model.environment;

import java.awt.*;
import java.util.*;

import model.characters.GameConfig;

public class Obstacle {
    public enum CellType { WALL, BLOCKER, LAVA }

    private final Map<Point, CellType> cells = new HashMap<>();
    private static final int cell = GameConfig.CELL_SIZE;

    public void buildLevel(int cols, int rows) {
        int centerY = rows / 2;
        int centerX = cols  / 2;
        for (int i = 0; i < cols; i++) {
            for (int j = 0; j < rows; j++) {
                if (j >= rows - 2)
                    cells.put(new Point(i, j), CellType.LAVA);
                else if ((j >= centerY) || (i >= centerX - 10 && i < centerX + 10))
                    cells.put(new Point(i, j), CellType.WALL);
            }
        }
    }

    public void addObstacle(int x, int y) { cells.put(new Point(x, y), CellType.WALL); }
    public void addBlocker(int x, int y) { cells.put(new Point(x, y), CellType.BLOCKER); }
    public void removeObstacle(int x, int y) { cells.remove(new Point(x, y)); }

    public boolean isCollision(int x, int y) {
        CellType t = cells.get(new Point(x, y));
        return t == CellType.WALL || t == CellType.BLOCKER;
    }
    public boolean isAtLava(int x, int y) { return cells.get(new Point(x, y)) == CellType.LAVA; }
    public boolean isBlockerAt(int x, int y) { return cells.get(new Point(x, y)) == CellType.BLOCKER; }

    public void draw(Graphics g) {
        for (Map.Entry<Point, CellType> entry : cells.entrySet()) {
            Point p = entry.getKey();
            g.setColor(switch (entry.getValue()) {
                case WALL -> Color.BLACK;
                case BLOCKER -> new Color(0x1a, 0x1a, 0x4e);
                case LAVA -> Color.RED;
            });
            g.fillRect(p.x * cell, p.y * cell, cell, cell);
        }
    }
}