package model;

import java.util.*;

public class TronModel {
    public static final int VOID = 0;
    public static final int TRACE_P1 = 1;
    public static final int TRACE_P2 = 2;
    public static final int OBSTACLE = 3;

    private final int row;
    private final int col;
    private final int obstacleCount;
    private final int[][] grid;
    private final boolean[][] floodVisited;
    private final Random random = new Random();

    private int P1R, P1C, P2R, P2C;

    private int dirP1R, dirP1C;
    private int dirP2R, dirP2C;
    private boolean P1Alive, P2Alive;

    private boolean gameOver = false;
    private String messageFin = "";

    public TronModel(int row, int col, int obstacleCount) {
        this.row = row;
        this.col = col;
        this.obstacleCount = obstacleCount;
        this.grid = new int[row][col];
        this.floodVisited = new boolean[row][col];
        initialise();
    }

    public void initialise() {
        for (int i = 0; i < row; i++) {
            for (int j = 0; j < col; j++) {
                grid[i][j] = VOID;
            }
        }

        P1R = row / 2;
        P1C = 2;
        P2R = row / 2;
        P2C = col - 3;

        dirP1R = 0;
        dirP1C = 1;
        dirP2R = 0;
        dirP2C = -1;

        P1Alive = true;
        P2Alive = true;

        grid[P1R][P1C] = TRACE_P1;
        grid[P2R][P2C] = TRACE_P2;

        addObstacles(obstacleCount);

        gameOver = false;
        messageFin = "";
    }

    private void addObstacles(int nb) {
        int count = 0;
        int attempts = 0;
        int maxAttempts = row * col * 10;

        while (count < nb && attempts < maxAttempts) {
            attempts++;

            int r = random.nextInt(row);
            int c = random.nextInt(col);

            if (grid[r][c] == VOID
                    && !nearbyPlayer(r, c, P1R, P1C)
                    && !nearbyPlayer(r, c, P2R, P2C)) {
                grid[r][c] = OBSTACLE;
                count++;
            }
        }
    }

    private boolean nearbyPlayer(int l, int c, int playerR, int playerC) {
        return Math.abs(l - playerR) <= 5 && Math.abs(c - playerC) <= 5;
    }

    public boolean[] step(boolean ia1, boolean hunt1, boolean ia2, boolean hunt2) {
        int[] dir1 = ia1 ? IAPath(1, hunt1) : null;
        int[] dir2 = ia2 ? IAPath(2, hunt2) : null;

        if (dir1 != null) changeDirection(1, dir1[0], dir1[1]);
        if (dir2 != null) changeDirection(2, dir2[0], dir2[1]);

        boolean stuck1 = ia1 && dir1 == null;
        boolean stuck2 = ia2 && dir2 == null;

        int next1R = P1R + dirP1R;
        int next1C = P1C + dirP1C;
        int next2R = P2R + dirP2R;
        int next2C = P2C + dirP2C;

        boolean hit1 = stuck1 || collision(next1R, next1C);
        boolean hit2 = stuck2 || collision(next2R, next2C);

        if (!hit1 && !hit2 && next1R == next2R && next1C == next2C) {
            hit1 = true;
            hit2 = true;
        }

        if (hit1) P1Alive = false;
        else {
            P1R = next1R;
            P1C = next1C;
            grid[next1R][next1C] = TRACE_P1;
        }

        if (hit2) P2Alive = false;
        else {
            P2R = next2R;
            P2C = next2C;
            grid[next2R][next2C] = TRACE_P2;
        }

        return new boolean[]{!hit1, !hit2};
    }

    private int[] IAPath(int player, boolean hunt) {
        int myR = getPlayerR(player);
        int myC = getPlayerC(player);

        int targetR = getPlayerR(otherPlayer(player));
        int targetC = getPlayerC(otherPlayer(player));

        int[][] directions = {
            {-1, 0},
            {1, 0},
            {0, -1},
            {0, 1}
        };

        List<int[]> candidates = new ArrayList<>();
        List<Integer> areas = new ArrayList<>();
        int bestArea = -1;

        for (int[] d : directions) {
            int nr = myR + d[0];
            int nc = myC + d[1];

            if (collision(nr, nc)) continue;

            int area = floodFillArea(nr, nc);
            candidates.add(d);
            areas.add(area);
            if (area > bestArea) bestArea = area;
        }

        if (candidates.isEmpty()) return null;

        int safeThreshold = Math.min(bestArea, 4);
        List<int[]> safeMoves = new ArrayList<>();
        List<Integer> safeAreas = new ArrayList<>();

        for (int i = 0; i < candidates.size(); i++) {
            if (areas.get(i) >= safeThreshold) {
                safeMoves.add(candidates.get(i));
                safeAreas.add(areas.get(i));
            }
        }

        List<int[]> best = new ArrayList<>();
        int bestScore = Integer.MAX_VALUE;

        for (int i = 0; i < safeMoves.size(); i++) {
            int[] d = safeMoves.get(i);
            int score = hunt
                    ? distance(targetR, targetC, myR + d[0], myC + d[1])
                    : -safeAreas.get(i);

            if (score < bestScore) {
                bestScore = score;
                best.clear();
                best.add(d);
            } else if (score == bestScore) {
                best.add(d);
            }
        }

        return best.get(random.nextInt(best.size()));
    }

    private int floodFillArea(int startR, int startC) {
        if (collision(startR, startC)) return 0;

        List<int[]> visitedCells = new ArrayList<>();
        Deque<int[]> queue = new ArrayDeque<>();

        queue.add(new int[]{startR, startC});
        floodVisited[startR][startC] = true;
        visitedCells.add(new int[]{startR, startC});
        int area = 0;

        int[][] neighbours = { {-1, 0}, {1, 0}, {0, -1}, {0, 1} };

        while (!queue.isEmpty()) {
            int[] cell = queue.poll();
            area++;

            for (int[] d : neighbours) {
                int nr = cell[0] + d[0];
                int nc = cell[1] + d[1];

                if (nr < 0 || nr >= row || nc < 0 || nc >= col) continue;
                if (floodVisited[nr][nc] || grid[nr][nc] != VOID) continue;

                floodVisited[nr][nc] = true;
                int[] next = new int[]{nr, nc};
                visitedCells.add(next);
                queue.add(next);
            }
        }

        for (int[] cell : visitedCells) floodVisited[cell[0]][cell[1]] = false;

        return area;
    }

    private boolean collision(int l, int c) {
        if (l < 0 || l >= row || c < 0 || c >= col) return true;
        return grid[l][c] != VOID;
    }

    public void changeDirection(int player, int dr, int dc) {
        if (player == 1) {
            if (dirP1R == -dr && dirP1C == -dc) return;
            dirP1R = dr;
            dirP1C = dc;
        } else {
            if (dirP2R == -dr && dirP2C == -dc) return;
            dirP2R = dr;
            dirP2C = dc;
        }
    }

    public void setGameOver(String message) {
        gameOver = true;
        messageFin = message;
    }

    public int[][] getGrid() { return grid; }
    public int getRow() { return row; }
    public int getCol() { return col; }
    public int getP1R() { return P1R; }
    public int getP1C() { return P1C; }
    public int getP2R() { return P2R; }
    public int getP2C() { return P2C; }
    private int getPlayerR(int player) { return (player == 1) ? P1R : P2R; }
    private int getPlayerC(int player) { return (player == 1) ? P1C : P2C; }
    private int otherPlayer(int player) { return (player == 1) ? 2 : 1; }
    public boolean isP1Alive() { return P1Alive; }
    public boolean isP2Alive() { return P2Alive; }
    private int distance(int r1, int c1, int r2, int c2) { return Math.abs(r1 - r2) + Math.abs(c1 - c2); }
    public boolean isGameOver() { return gameOver; }
    public String getMessageFin() { return messageFin; }
}
