package model.track;

import java.util.*;

import model.GameConfig;
import model.track.Track.*;

public class TrackFactory {
    private static final List<Cell> CONTROL_POINTS = List.of(
        new Cell(8, 17), // point de départ
        new Cell(8, 1), // 1er tournant
        new Cell(2, 1),
        new Cell(2, 12),
        new Cell(5, 12),
        new Cell(5, 15),
        new Cell(1, 15),
        new Cell(1, 18), // Dernier tournant
        new Cell(8, 18) // point d'arrive
    );
    private static final int[] TURN_LIMITS = { 5, 3, 2, 2, 2, 2, 5 };

    public static Track circuit() {
        int rows = GameConfig.Track.ROWS;
        int cols = GameConfig.Track.COLS;

        Type[][] g = new Type[rows][cols];
        for (int r = 0; r < rows; r++) Arrays.fill(g[r], Type.GRASS);

        List<Cell> road = buildRoad(CONTROL_POINTS);
        for (Cell c : road) g[c.row()][c.col()] = Type.ROAD;

        Cell start = CONTROL_POINTS.get(0);
        Cell finish = CONTROL_POINTS.get(CONTROL_POINTS.size() - 1);
        g[start.row()][start.col()] = Type.START;
        g[finish.row()][finish.col()] = Type.FINISH;

        Map<Cell, Integer> turns = new HashMap<>();
        for (int i = 1; i < CONTROL_POINTS.size() - 1; i++) {
            Cell c = CONTROL_POINTS.get(i);
            turns.put(c, TURN_LIMITS[i - 1]);
            g[c.row()][c.col()] = Type.TURN;
        }
        return new Track(rows, cols, g, road, road.indexOf(start), road.indexOf(finish), turns);
    }

    public static List<Cell> buildRoad(List<Cell> pts) {
        List<Cell> r = new ArrayList<>();
        for (int i = 0; i < pts.size() - 1; i++) {
            appendSegment(r, pts.get(i), pts.get(i + 1));
        }
        return r;
    }

    private static void appendSegment(List<Cell> r, Cell a, Cell b) {
        int row = a.row(), col = a.col();
        if (r.isEmpty() || !r.get(r.size() - 1).equals(a)) r.add(new Cell(row, col));
        while (row != b.row() || col != b.col()) {
            if (row != b.row()) row += Integer.compare(b.row(), row);
            else col += Integer.compare(b.col(), col);
            r.add(new Cell(row, col));
        }
    }
}
