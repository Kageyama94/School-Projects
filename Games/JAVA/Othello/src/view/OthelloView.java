package view;

import model.Piece;

import javax.swing.*;
import java.awt.*;
import java.awt.event.MouseListener;
import java.util.List;

public class OthelloView extends JFrame {
    private BoardPanel boardPanel;
    private JLabel statusLabel;

    public OthelloView() {
        setTitle("Othello MVC");
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        setSize(700, 780);
        setMinimumSize(new Dimension(300, 380));
        setLocationRelativeTo(null);
        setLayout(new BorderLayout());

        boardPanel = new BoardPanel();

        statusLabel = new JLabel("Tour du joueur Noir", SwingConstants.CENTER);
        statusLabel.setFont(new Font("Arial", Font.BOLD, 18));

        JPanel bottomPanel = new JPanel(new BorderLayout());
        bottomPanel.add(statusLabel, BorderLayout.CENTER);

        add(boardPanel, BorderLayout.CENTER);
        add(bottomPanel, BorderLayout.SOUTH);
    }

    public void updateBoard(Piece[][] board) { boardPanel.setBoard(board); }
    public void setValidMoves(List<int[]> moves) { boardPanel.setValidMoves(moves); }
    public void setStatus(String text) { statusLabel.setText(text); }
    public void addBoardMouseListener(MouseListener l) { boardPanel.addMouseListener(l); }
    public int getCellSize() { return boardPanel.getCellSize(); }
    public int getBoardOffsetX() { return boardPanel.getOffsetX(); }
    public int getBoardOffsetY() { return boardPanel.getOffsetY(); }
    public void showMessage(String message) { JOptionPane.showMessageDialog(this, message); }

    public void animateFlips(List<int[]> flipped, Piece fromColor, Piece toColor, Runnable onDone) {
        boardPanel.animateFlips(flipped, fromColor, toColor, onDone);
    }

    public int showEndDialog(String message) {
        return JOptionPane.showConfirmDialog(this, message, "Fin de partie", JOptionPane.YES_NO_OPTION);
    }
}