package app;

import controller.*;
import model.OthelloAI;
import model.OthelloModel;
import view.OthelloView;

import javax.swing.*;

public class Main {
    public static void main(String[] args) {
        SwingUtilities.invokeLater(() -> {
            String[] options = {
                "Humain vs Humain",
                "Humain vs IA (aléatoire)",
                "Humain vs IA (Minimax)"
            };

            int choice = JOptionPane.showOptionDialog(
                    null,
                    "Choisissez un mode de jeu :",
                    "Othello",
                    JOptionPane.DEFAULT_OPTION,
                    JOptionPane.QUESTION_MESSAGE,
                    null,
                    options,
                    options[0]
            );

            if (choice < 0) { System.exit(0); return; }

            GameMode mode = switch (choice) {
                case 1 -> GameMode.HUMAN_VS_RANDOM_AI;
                case 2 -> GameMode.HUMAN_VS_MINIMAX_AI;
                default -> GameMode.HUMAN_VS_HUMAN;
            };

            int aiDepth = OthelloAI.DEFAULT_DEPTH;
            if (mode == GameMode.HUMAN_VS_MINIMAX_AI) {
                String[] difficulties = {"Facile", "Moyen", "Difficile"};
                int[] depths = {2, 6, 8};

                int diffChoice = JOptionPane.showOptionDialog(
                        null,
                        "Choisissez la difficulté :",
                        "Othello",
                        JOptionPane.DEFAULT_OPTION,
                        JOptionPane.QUESTION_MESSAGE,
                        null,
                        difficulties,
                        difficulties[1]
                );

                if (diffChoice < 0) { System.exit(0); return; }
                aiDepth = depths[diffChoice];
            }

            OthelloModel model = new OthelloModel(aiDepth);
            OthelloView view = new OthelloView();
            new OthelloController(model, view, mode);

            view.setVisible(true);
        });
    }
}