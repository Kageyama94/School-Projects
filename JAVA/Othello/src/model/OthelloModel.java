package model;

import java.util.*;

public class OthelloModel {
    public static final int SIZE = 8;

    private Piece[][] board;
    private Piece currentPlayer;
    private Random random;
    private OthelloAI ai;

    private List<int[]> lastFlipped = new ArrayList<>();

    public OthelloModel() { this(OthelloAI.DEFAULT_DEPTH); }

    public OthelloModel(int aiDepth) {
        board = new Piece[SIZE][SIZE];
        random = new Random();
        ai = new OthelloAI(aiDepth);
        resetGame();
    }

    public void resetGame() {
        for (int i = 0; i < SIZE; i++)
            for (int j = 0; j < SIZE; j++)
                board[i][j] = Piece.EMPTY;

        board[3][3] = Piece.WHITE;
        board[3][4] = Piece.BLACK;
        board[4][3] = Piece.BLACK;
        board[4][4] = Piece.WHITE;

        currentPlayer = Piece.BLACK;
        lastFlipped.clear();
    }

    public Piece[][] getBoard() { return board; }
    public Piece getCurrentPlayer() { return currentPlayer; }
    public List<int[]> getLastFlipped() { return lastFlipped; }

    public void skipTurn() { currentPlayer = currentPlayer.opposite(); }

    public boolean isValidMove(int row, int col, Piece player) {
        if (!isInside(row, col) || board[row][col] != Piece.EMPTY) return false;

        Piece opponent = player.opposite();

        for (int d = 0; d < 8; d++) {
            int x = row + Directions.DX[d], y = col + Directions.DY[d];
            boolean foundOpponent = false;
            while (isInside(x, y) && board[x][y] == opponent) { x += Directions.DX[d]; y += Directions.DY[d]; foundOpponent = true; }
            if (foundOpponent && isInside(x, y) && board[x][y] == player) return true;
        }
        return false;
    }

    public boolean playMove(int row, int col) {
        if (!isValidMove(row, col, currentPlayer)) return false;

        board[row][col] = currentPlayer;
        lastFlipped = flipPieces(row, col, currentPlayer);

        currentPlayer = currentPlayer.opposite();

        return true;
    }

    private List<int[]> flipPieces(int row, int col, Piece player) {
        Piece opponent = player.opposite();
        List<int[]> allFlipped = new ArrayList<>();

        for (int d = 0; d < 8; d++) {
            int x = row + Directions.DX[d], y = col + Directions.DY[d];
            List<int[]> toFlip = new ArrayList<>();

            while (isInside(x, y) && board[x][y] == opponent) {
                toFlip.add(new int[]{x, y});
                x += Directions.DX[d]; y += Directions.DY[d];
            }

            if (isInside(x, y) && board[x][y] == player) {
                for (int[] pos : toFlip) board[pos[0]][pos[1]] = player;
                allFlipped.addAll(toFlip);
            }
        }
        return allFlipped;
    }

    public boolean hasValidMove(Piece player) {
        for (int i = 0; i < SIZE; i++)
            for (int j = 0; j < SIZE; j++)
                if (isValidMove(i, j, player)) return true;
        return false;
    }

    public List<int[]> getValidMoves(Piece player) { return staticGetValidMoves(board, player); }

    public static List<int[]> staticGetValidMoves(Piece[][] board, Piece player) {
        List<int[]> validMoves = new ArrayList<>();
        Piece opponent = player.opposite();

        for (int i = 0; i < SIZE; i++) {
            for (int j = 0; j < SIZE; j++) {
                if (board[i][j] != Piece.EMPTY) continue;
                outer:
                for (int d = 0; d < 8; d++) {
                    int x = i + Directions.DX[d], y = j + Directions.DY[d];
                    boolean foundOpponent = false;
                    while (x >= 0 && x < SIZE && y >= 0 && y < SIZE && board[x][y] == opponent) {
                        x += Directions.DX[d]; y += Directions.DY[d]; foundOpponent = true;
                    }
                    if (foundOpponent && x >= 0 && x < SIZE && y >= 0 && y < SIZE && board[x][y] == player) {
                        validMoves.add(new int[]{i, j}); break outer;
                    }
                }
            }
        }
        return validMoves;
    }

    public boolean playAIMove(Piece player) {
        if (currentPlayer != player) return false;
        int[] move = ai.bestMove(board, player);
        if (move == null) return false;
        return playMove(move[0], move[1]);
    }

    public boolean playRandomMove(Piece player) {
        if (currentPlayer != player) return false;
        List<int[]> validMoves = getValidMoves(player);
        if (validMoves.isEmpty()) return false;
        int[] move = validMoves.get(random.nextInt(validMoves.size()));
        return playMove(move[0], move[1]);
    }

    public boolean isGameOver() { return !hasValidMove(Piece.BLACK) && !hasValidMove(Piece.WHITE); }

    public int countPieces(Piece piece) {
        int count = 0;
        for (int i = 0; i < SIZE; i++)
            for (int j = 0; j < SIZE; j++)
                if (board[i][j] == piece) count++;
        return count;
    }

    public String getWinner() {
        int black = countPieces(Piece.BLACK), white = countPieces(Piece.WHITE);
        if (black > white) return "Noir gagne !";
        if (white > black) return "Blanc gagne !";
        return "Égalité !";
    }

    private boolean isInside(int row, int col) { return row >= 0 && row < SIZE && col >= 0 && col < SIZE; }
}