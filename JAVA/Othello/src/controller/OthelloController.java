package controller;

import model.*;
import view.OthelloView;

import javax.swing.SwingWorker;
import java.awt.event.*;
import java.util.List;

public class OthelloController {
    private OthelloModel model;
    private OthelloView view;
    private GameMode mode;

    private boolean animationInProgress = false;

    public OthelloController(OthelloModel model, OthelloView view, GameMode mode) {
        this.model = model;
        this.view = view;
        this.mode = mode;

        view.addBoardMouseListener(new BoardMouseListener());
        refreshView();
    }

    private void refreshView() {
        view.updateBoard(model.getBoard());

        int black = model.countPieces(Piece.BLACK);
        int white = model.countPieces(Piece.WHITE);

        if (model.isGameOver()) {
            view.setValidMoves(null);
            view.setStatus("<html><div style='text-align:center;'>Noir: " + black +
                    " | Blanc: " + white + "<br>Partie terminée</div></html>");

            int choice = view.showEndDialog(model.getWinner() +
                    "\nNoir: " + black + " | Blanc: " + white + "\nVoulez-vous recommencer ?");
            if (choice == 0) { model.resetGame(); refreshView(); }
            else System.exit(0);
            return;
        }

        Piece current = model.getCurrentPlayer();
        String playerName = (current == Piece.BLACK) ? "Noir" : "Blanc";

        // Passe-tour
        if (!model.hasValidMove(current)) {
            view.setValidMoves(null);
            view.setStatus("<html><div style='text-align:center;'>Noir: " + black +
                    " | Blanc: " + white + "<br>" + playerName + " passe son tour !</div></html>");
            view.showMessage("Le joueur " + playerName + " n'a aucun coup valide et passe son tour.");
            model.skipTurn();
            refreshView();
            return;
        }

        view.setStatus("<html><div style='text-align:center;'>Noir: " + black +
                " | Blanc: " + white + "<br>Tour du joueur " + playerName + "</div></html>");

        boolean humanTurn = !isVsAI() || current == Piece.BLACK;
        view.setValidMoves(humanTurn ? model.getValidMoves(current) : null);

        if (isVsAI() && current == Piece.WHITE) playAIThenAnimate();
    }

    private void animateThenRefresh(Piece playerBeforeMove) {
        List<int[]> flipped = model.getLastFlipped();

        if (flipped.isEmpty()) {
            refreshView(); return;
        }

        animationInProgress = true;
        view.setValidMoves(null);

        Piece fromColor = playerBeforeMove.opposite();

        view.animateFlips(flipped, fromColor, playerBeforeMove, () -> {
            animationInProgress = false;
            refreshView();
        });
    }

    private void playAIThenAnimate() {
        Piece aiColor = Piece.WHITE;
        animationInProgress = true;

        int black = model.countPieces(Piece.BLACK);
        int white = model.countPieces(Piece.WHITE);
        view.setValidMoves(null);
        view.setStatus("<html><div style='text-align:center;'>Noir: " + black +
                " | Blanc: " + white + "<br>L'IA réfléchit...</div></html>");

        new SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() {
                playAI();
                return null;
            }
            @Override
            protected void done() {
                try {
                    get();
                } catch (Exception ex) {
                    animationInProgress = false;
                    view.showMessage("Erreur inattendue pendant le calcul de l'IA : " + ex.getCause());
                    return;
                }
                animateThenRefresh(aiColor);
            }
        }.execute();
    }

    private boolean isVsAI() { return mode == GameMode.HUMAN_VS_RANDOM_AI || mode == GameMode.HUMAN_VS_MINIMAX_AI; }

    private void playAI() {
        if (mode == GameMode.HUMAN_VS_MINIMAX_AI) model.playAIMove(Piece.WHITE);
        else model.playRandomMove(Piece.WHITE);
    }

    private class BoardMouseListener extends MouseAdapter {
        @Override
        public void mouseClicked(MouseEvent e) {
            if (animationInProgress) return;
            if (model.isGameOver()) return;
            if (isVsAI() && model.getCurrentPlayer() == Piece.WHITE) return;

            int col = (e.getX() - view.getBoardOffsetX()) / view.getCellSize();
            int row = (e.getY() - view.getBoardOffsetY()) / view.getCellSize();

            if (e.getX() < view.getBoardOffsetX() || e.getY() < view.getBoardOffsetY()) return;
            if (row < 0 || row >= OthelloModel.SIZE || col < 0 || col >= OthelloModel.SIZE) return;

            Piece playerBeforeMove = model.getCurrentPlayer();
            boolean played = model.playMove(row, col);

            if (!played) { view.showMessage("Coup invalide !"); return; }

            animateThenRefresh(playerBeforeMove);
        }
    }
}