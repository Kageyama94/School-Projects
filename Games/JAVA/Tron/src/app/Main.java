package app;

import controller.TronController;
import model.TronModel;
import view.TronView;

import javax.swing.*;

public class Main {
    public static void main(String[] args) {
        SwingUtilities.invokeLater(() -> {
            String[] options = {"Humain vs Humain", "Humain vs IA", "IA vs IA"};

            int choice = JOptionPane.showOptionDialog(
                    null,
                    "Choisissez un mode de jeu :",
                    "Tron",
                    JOptionPane.DEFAULT_OPTION,
                    JOptionPane.QUESTION_MESSAGE,
                    null,
                    options,
                    options[0]
            );

            if (choice == -1) System.exit(0);

            TronModel model = new TronModel(20, 30);
            TronView view = new TronView(model);
            new TronController(model, view, choice);
        });
    }
}