package view;

import javax.swing.*;
import java.awt.*;

import model.TronModel;

public class TronView extends JFrame {
    private final BoardPanel boardPanel;

    public TronView(TronModel model) {
        setTitle("Tron");
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        setLayout(new BorderLayout());

        boardPanel = new BoardPanel(model);
        add(boardPanel, BorderLayout.CENTER);

        pack();
        setLocationRelativeTo(null);
        setResizable(false);
        setVisible(true);
    }

    public void refresh() { boardPanel.repaint(); }

    public boolean askReplay(String message) {
        int result = JOptionPane.showConfirmDialog(
            this,
            message + "\n\nRejouer ?",
            "Fin de partie",
            JOptionPane.YES_NO_OPTION,
            JOptionPane.INFORMATION_MESSAGE
        );
        return result == JOptionPane.YES_OPTION;
    }
}