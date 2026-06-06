package model;

import java.util.*;

public class TronModel {
    public static final int VOID = 0;
    public static final int TRACE_P1 = 1;
    public static final int TRACE_P2 = 2;
    public static final int OBSTACLE = 3;

    private final int row;
    private final int col;
    private final int[][] grid;
    private final Random random = new Random();

    private int P1R, P1C, P2R, P2C;

    private int dirP1R = 0, dirP1C = 1;
    private int dirP2R = 0, dirP2C = -1;
    private boolean P1Alive, P2Alive;

    private boolean gameOver = false;
    private String messageFin = "";

    public TronModel(int row, int col) {
        this.row = row;
        this.col = col;
        this.grid = new int[row][col];
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

        P1Alive = true;
        P2Alive = true;

        grid[P1R][P1C] = TRACE_P1;
        grid[P2R][P2C] = TRACE_P2;

        addObstacles(25);

        gameOver = false;
        messageFin = "";
    }

    private void addObstacles(int nb) {
        int count = 0;

        while (count < nb) {
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

    private boolean nearbyPlayer(int l, int c, int playerR, int PlayerC) {
        return Math.abs(l - playerR) <= 5 && Math.abs(c - PlayerC) <= 5;
    }

    public boolean advancePlayer(int player) {
        int r = getPlayerR(player);
        int c = getPlayerC(player);
        int dr = getDirR(player);
        int dc = getDirC(player);

        int nextR = r + dr;
        int nextC = c + dc;

        if (collision(nextR, nextC)) {
            if (player == 1) P1Alive = false;
            else P2Alive = false;
            return false;
        }

        setPlayerPosition(player, nextR, nextC);
        grid[nextR][nextC] = (player == 1) ? TRACE_P1 : TRACE_P2;
        return true;
    }

    public boolean advanceIA(int player, boolean chasse) {
        int[] direction = IAPath(player, chasse);

        if (direction == null) return false;

        changeDirection(player, direction[0], direction[1]);
        return advancePlayer(player);
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

        List<int[]> validMoves = new ArrayList<>();

        for (int[] d : directions) {
            int nr = myR + d[0];
            int nc = myC + d[1];

            if (!collision(nr, nc)) validMoves.add(d);
        }

        if (validMoves.isEmpty()) return null;
        if (!hunt) return validMoves.get(random.nextInt(validMoves.size()));

        int[] best = validMoves.get(0);
        int bestDistance = distance(targetR, targetC, myR + best[0], myC + best[1]);

        for (int[] d : validMoves) {
            int nr = myR + d[0];
            int nc = myC + d[1];
            int currentDistance = distance(targetR, targetC, nr, nc);
            
            if (currentDistance < bestDistance) {
                bestDistance = currentDistance;
                best = d;
            }
        }
        return best;
    }

    private void setPlayerPosition(int player, int r, int c) {
        if (player == 1) {
            P1R = r;
            P1C = c;
        } else {
            P2R = r;
            P2C = c;
        }
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
    private int getDirR(int player) { return (player == 1) ? dirP1R : dirP2R; }
    private int getDirC(int player) { return (player == 1) ? dirP1C : dirP2C; }
    public boolean isGameOver() { return gameOver; }
    public String getMessageFin() { return messageFin; }
}