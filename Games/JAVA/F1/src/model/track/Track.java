package model.track;

import java.util.*;

public class Track {
    public record Cell(int row, int col) {}
    public enum Type { GRASS, ROAD, START, FINISH, TURN }
    private final int rows, cols;
    private final Type[][] grid;
    private final List<Cell> road;
    private final int startIndex, finishIndex;
    private final Map<Cell, Integer> turns;

    public Track(int rows, int cols, Type[][] grid,
                 List<Cell> road, int startIndex, int finishIndex,
                 Map<Cell, Integer> turns) {
        this.rows = rows;
        this.cols = cols;
        this.grid = grid;
        this.road = List.copyOf(road);
        this.startIndex = startIndex;
        this.finishIndex = finishIndex;
        this.turns = new HashMap<>(turns);
    }

    public int advanceOnRoad(int roadIndex, int steps) {
        int n = road.size();
        int idx = (roadIndex + steps) % n;
        if (idx < 0) idx += n;
        return idx;
    }

    public boolean crossedFinish(int beforeIndex, int afterIndex) {
        if (beforeIndex == afterIndex) return false;
        if (beforeIndex < finishIndex) return afterIndex >= finishIndex || afterIndex < beforeIndex;
        if (beforeIndex > finishIndex) return afterIndex >= finishIndex && afterIndex < beforeIndex;
        return false;
    }

    // Règle : la voiture crashe si le nombre total de pas atteint (ou dépasse)
    // la distance jusqu'au virage + la limite du virage.
    // Ex : virage à 3 cases devant, limite 2 → crash si |steps| >= 5.
    // Fonctionne dans les deux sens (marche avant et marche arrière).
    public boolean isDamaged(int beforeIndex, int steps) {
        if (steps == 0) return false;

        int direction = Integer.signum(steps);
        int absSteps = Math.abs(steps);

        Integer limit = turns.get(road.get(beforeIndex));
        if (limit != null) return absSteps >= limit;

        int idx = advanceOnRoad(beforeIndex, direction);
        int distanceToTurn = 0;

        for (int i = 1; i <= absSteps; i++) {
            limit = turns.get(road.get(idx));
            if (limit != null) return absSteps >= (distanceToTurn + limit);
            distanceToTurn++;
            idx = advanceOnRoad(idx, direction);
        }
        return false;
    }

    public List<Cell> getRoad() { return road; }
    public int getStartIndex() { return startIndex; }
    public int rows() { return rows; }
    public int cols() { return cols; }
    public Type get(int r, int c) { return grid[r][c]; }
    public Integer getTurnAt(int r, int c) { return turns.get(new Cell(r, c)); }
}