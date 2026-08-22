package app;

import controller.GameMode;
import controller.TronController;
import model.TronModel;
import view.TronView;

import javax.swing.*;

public class Main {
    public static void main(String[] args) {
        SwingUtilities.invokeLater(() -> {
            String[] modeOptions = {"Humain vs Humain", "Humain vs IA", "IA vs IA"};

            int modeChoice = JOptionPane.showOptionDialog(
                    null,
                    "Choisissez un mode de jeu :",
                    "Tron",
                    JOptionPane.DEFAULT_OPTION,
                    JOptionPane.QUESTION_MESSAGE,
                    null,
                    modeOptions,
                    modeOptions[0]
            );

            if (modeChoice == -1) System.exit(0);

            GameMode mode = GameMode.values()[modeChoice];

            TronModel model = new TronModel(20, 30, 25);
            TronView view = new TronView(model);
            new TronController(model, view, mode);
        });
    }
}
