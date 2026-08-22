package model;

import java.util.List;

public class OthelloAI {
    public static final int DEFAULT_DEPTH = 6;

    private static final int[][] POSITION_WEIGHTS = {
        { 100, -20,  10,   5,   5,  10, -20, 100 },
        { -20, -50,  -2,  -2,  -2,  -2, -50, -20 },
        {  10,  -2,   5,   1,   1,   5,  -2,  10 },
        {   5,  -2,   1,   0,   0,   1,  -2,   5 },
        {   5,  -2,   1,   0,   0,   1,  -2,   5 },
        {  10,  -2,   5,   1,   1,   5,  -2,  10 },
        { -20, -50,  -2,  -2,  -2,  -2, -50, -20 },
        { 100, -20,  10,   5,   5,  10, -20, 100 }
    };

    private final int depth;

    public OthelloAI() { this(DEFAULT_DEPTH); }
    public OthelloAI(int depth) { this.depth = depth; }

    public int[] bestMove(Piece[][] board, Piece player) {
        List<int[]> moves = OthelloModel.staticGetValidMoves(board, player);
        if (moves.isEmpty()) return null;

        int[] best = null;
        int bestScore = Integer.MIN_VALUE;

        for (int[] move : moves) {
            Piece[][] next = applyMove(board, move[0], move[1], player);
            int score = minimax(next, depth - 1, Integer.MIN_VALUE, Integer.MAX_VALUE,
                                false, player, player.opposite());
            if (score > bestScore) {
                bestScore = score;
                best = move;
            }
        }
        return best;
    }

    private int minimax(Piece[][] board, int depth,
                        int alpha, int beta,
                        boolean maximizing,
                        Piece aiPlayer, Piece currentTurn) {

        if (depth == 0 || isTerminal(board)) return evaluate(board, aiPlayer);

        List<int[]> moves = OthelloModel.staticGetValidMoves(board, currentTurn);

        // Pas de coup disponible → passe le tour à l'adversaire
        if (moves.isEmpty()) {
            Piece opponent = currentTurn.opposite();
            if (OthelloModel.staticGetValidMoves(board, opponent).isEmpty()) return evaluate(board, aiPlayer);
            return minimax(board, depth - 1, alpha, beta, !maximizing, aiPlayer, opponent);
        }

        if (maximizing) {
            int maxEval = Integer.MIN_VALUE;
            for (int[] move : moves) {
                Piece[][] next = applyMove(board, move[0], move[1], currentTurn);
                int eval = minimax(next, depth - 1, alpha, beta, false, aiPlayer, currentTurn.opposite());
                maxEval = Math.max(maxEval, eval);
                alpha = Math.max(alpha, eval);
                if (beta <= alpha) break; // coupure bêta
            }
            return maxEval;
        } else {
            int minEval = Integer.MAX_VALUE;
            for (int[] move : moves) {
                Piece[][] next = applyMove(board, move[0], move[1], currentTurn);
                int eval = minimax(next, depth - 1, alpha, beta, true, aiPlayer, currentTurn.opposite());
                minEval = Math.min(minEval, eval);
                beta = Math.min(beta, eval);
                if (beta <= alpha) break; // coupure alpha
            }
            return minEval;
        }
    }
    
    private int positionWeight(Piece[][] board, int row, int col) {
        int base = POSITION_WEIGHTS[row][col];
        if (base >= 0) return base;

        int cornerRow = (row < OthelloModel.SIZE / 2) ? 0 : OthelloModel.SIZE - 1;
        int cornerCol = (col < OthelloModel.SIZE / 2) ? 0 : OthelloModel.SIZE - 1;
        if (board[cornerRow][cornerCol] != Piece.EMPTY) return 0;

        return base;
    }

    private int evaluate(Piece[][] board, Piece aiPlayer) {
        Piece opponent = aiPlayer.opposite();

        int posScore = 0;
        int aiCount = 0, opCount = 0;
        for (int r = 0; r < OthelloModel.SIZE; r++) {
            for (int c = 0; c < OthelloModel.SIZE; c++) {
                if (board[r][c] == aiPlayer) { posScore += positionWeight(board, r, c); aiCount++; }
                else if (board[r][c] == opponent) { posScore -= positionWeight(board, r, c); opCount++; }
            }
        }

        int aiMoves = OthelloModel.staticGetValidMoves(board, aiPlayer).size();
        int opMoves = OthelloModel.staticGetValidMoves(board, opponent).size();
        int mobilityScore = 0;
        if (aiMoves + opMoves > 0) mobilityScore = 10 * (aiMoves - opMoves) / (aiMoves + opMoves + 1);

        int total = aiCount + opCount;
        int parityWeight = (total > 50) ? 5 : 1;
        int parityScore = parityWeight * (aiCount - opCount);

        return posScore + mobilityScore + parityScore;
    }

    private boolean isTerminal(Piece[][] board) {
        return OthelloModel.staticGetValidMoves(board, Piece.BLACK).isEmpty()
            && OthelloModel.staticGetValidMoves(board, Piece.WHITE).isEmpty();
    }

    private Piece[][] applyMove(Piece[][] board, int row, int col, Piece player) {
        int size = OthelloModel.SIZE;
        Piece[][] next = new Piece[size][size];
        for (int r = 0; r < size; r++) next[r] = board[r].clone();

        next[row][col] = player;
        Piece opponent = player.opposite();

        for (int d = 0; d < 8; d++) {
            int x = row + Directions.DX[d], y = col + Directions.DY[d];
            java.util.List<int[]> toFlip = new java.util.ArrayList<>();

            while (x >= 0 && x < size && y >= 0 && y < size && next[x][y] == opponent) {
                toFlip.add(new int[]{x, y});
                x += Directions.DX[d]; y += Directions.DY[d];
            }

            if (x >= 0 && x < size && y >= 0 && y < size && next[x][y] == player) {
                for (int[] pos : toFlip) next[pos[0]][pos[1]] = player;
            }
        }
        return next;
    }
}