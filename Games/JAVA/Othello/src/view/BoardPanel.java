package view;

import model.OthelloModel;
import model.Piece;

import javax.swing.*;
import java.awt.*;
import java.util.*;
import java.util.List;
import javax.swing.Timer;

public class BoardPanel extends JPanel {
    private Piece[][] board;
    private List<int[]> validMoves = new ArrayList<>();

    private final Map<String, double[]> animating = new LinkedHashMap<>();
    private Piece animTargetColor = Piece.BLACK;
    private Timer animTimer;
    private Runnable onAnimDone;

    private static final double STEP = 0.06; // vitesse : ~10 frames pour demi-tour
    private static final int FPS = 16; // ms par frame (~60fps)

    private int cellSize, offsetX, offsetY;

    public BoardPanel() { setBackground(new Color(30, 120, 30)); }

    public void setBoard(Piece[][] board) {
        this.board = deepCopy(board);
        repaint();
    }

    private static Piece[][] deepCopy(Piece[][] board) {
        Piece[][] copy = new Piece[board.length][];
        for (int i = 0; i < board.length; i++) copy[i] = board[i].clone();
        return copy;
    }
    
    public void setValidMoves(List<int[]> moves) { this.validMoves = (moves != null) ? moves : new ArrayList<>(); repaint(); }
    public int getCellSize() { return cellSize; }
    public int getOffsetX() { return offsetX; }
    public int getOffsetY() { return offsetY; }

    public void animateFlips(List<int[]> flipped, Piece fromColor, Piece toColor, Runnable onDone) {
        if (flipped == null || flipped.isEmpty()) { if (onDone != null) onDone.run(); return; }

        animTargetColor = toColor;
        onAnimDone = onDone;
        animating.clear();

        for (int[] pos : flipped) {
            String key = pos[0] + "," + pos[1];
            animating.put(key, new double[]{ 0.0, fromColor == Piece.BLACK ? 0.0 : 1.0 });
        }

        if (animTimer != null) animTimer.stop();

        animTimer = new Timer(FPS, e -> {
            boolean allDone = true;
            for (double[] state : animating.values()) {
                state[0] = Math.min(1.0, state[0] + STEP);
                if (state[0] < 1.0) allDone = false;
            }
            repaint();
            if (allDone) {
                animTimer.stop();
                animating.clear();
                if (onAnimDone != null) onAnimDone.run();
            }
        });
        animTimer.start();
    }

    @Override
    protected void paintComponent(Graphics g) {
        super.paintComponent(g);

        int size = OthelloModel.SIZE;

        cellSize = Math.min((getWidth() - 40) / size, (getHeight() - 40) / size);
        int boardPixelSize = cellSize * size;
        offsetX = (getWidth() - boardPixelSize) / 2;
        offsetY = (getHeight() - boardPixelSize) / 2;

        Graphics2D g2 = (Graphics2D) g;
        g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);

        g2.setColor(new Color(0, 128, 0));
        g2.fillRect(offsetX, offsetY, boardPixelSize, boardPixelSize);

        g2.setColor(Color.BLACK);
        for (int i = 0; i <= size; i++) {
            g2.drawLine(offsetX, offsetY + i * cellSize, offsetX + boardPixelSize, offsetY + i * cellSize);
            g2.drawLine(offsetX + i * cellSize, offsetY, offsetX + i * cellSize, offsetY + boardPixelSize);
        }

        if (board == null) return;

        int margin = 6;

        if (!validMoves.isEmpty()) {
            int hintSize   = cellSize / 3;
            int hintOffset = (cellSize - hintSize) / 2;
            g2.setColor(new Color(0, 0, 0, 80));
            for (int[] mv : validMoves) {
                g2.fillOval(offsetX + mv[1] * cellSize + hintOffset,
                            offsetY + mv[0] * cellSize + hintOffset,
                            hintSize, hintSize);
            }
        }

        for (int row = 0; row < size; row++) {
            for (int col = 0; col < size; col++) {
                Piece piece = board[row][col];
                if (piece == Piece.EMPTY) continue;

                int px = offsetX + col * cellSize;
                int py = offsetY + row * cellSize;

                String key = row + "," + col;
                double[] state = animating.get(key);

                if (state != null) drawAnimatedPiece(g2, px, py, margin, state[0], state[1]);
                else drawStaticPiece(g2, px, py, margin, piece);
            }
        }
    }

    private void drawStaticPiece(Graphics2D g2, int px, int py, int margin, Piece piece) {
        int d = cellSize - 2 * margin;
        if (piece == Piece.BLACK) {
            g2.setColor(Color.BLACK);
            g2.fillOval(px + margin, py + margin, d, d);
        } else {
            g2.setColor(Color.WHITE);
            g2.fillOval(px + margin, py + margin, d, d);
            g2.setColor(Color.BLACK);
            g2.drawOval(px + margin, py + margin, d, d);
        }
    }

    /**
     *  Anime un retournement :
     *  progress 0.0→0.5 : écrasement horizontal (couleur de départ)
     *  progress 0.5→1.0 : déploiement (couleur cible)
     */
    private void drawAnimatedPiece(Graphics2D g2, int px, int py, int margin,
                                   double progress, double fromColorD) {
        Piece from = (fromColorD < 0.5) ? Piece.BLACK : Piece.WHITE;

        int fullD = cellSize - 2 * margin;
        int cy = py + margin + fullD / 2;

        double scale;
        Piece color;

        if (progress <= 0.5) {
            scale = 1.0 - progress * 2.0; // 1 → 0
            color = from;
        } else {
            scale = (progress - 0.5) * 2.0; // 0 → 1
            color = animTargetColor;
        }

        int w = Math.max(1, (int) (fullD * scale));
        int h = fullD;
        int x = px + margin + (fullD - w) / 2;
        int y = cy - h / 2;

        if (color == Piece.WHITE) {
            g2.setColor(Color.WHITE);
            g2.fillOval(x, y, w, h);
            g2.setColor(Color.BLACK);
            g2.drawOval(x, y, w, h);
        } else {
            g2.setColor(Color.BLACK);
            g2.fillOval(x, y, w, h);
        }
    }
}