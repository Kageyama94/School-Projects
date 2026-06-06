import javax.swing.*;
import java.awt.*;
import java.awt.event.*;

public class Main extends JPanel {
    private static final int N = 11;
    private static final int SIZE = 30;

    private Polygon[][] HEXS = new Polygon[N][N];
    private int[][] board = new int[N][N]; // 0 = VOID, 1 = RED, 2 = BLUE
    private int currentPlayer = 1;
    private boolean gameOver = false;
    private JLabel statusLabel;

    public Main(JLabel statusLabel) {
        this.statusLabel = statusLabel;
        setBackground(new Color(50, 50, 50));
        updateStatusLabel();

        addMouseListener(new MouseAdapter() {
            @Override
            public void mouseClicked(MouseEvent e) {
                if (gameOver) return;

                for (int row = 1; row < N - 1; row++) {
                    for (int col = 1; col < N - 1; col++) {
                        if (HEXS[row][col] != null
                                && HEXS[row][col].contains(e.getPoint())
                                && board[row][col] == 0) {

                            board[row][col] = currentPlayer;

                            if (hasWon(currentPlayer)) {
                                winner();
                                return;
                            }

                            currentPlayer = (currentPlayer == 1) ? 2 : 1;
                            updateStatusLabel();
                            repaint();
                            return;
                        }
                    }
                }
            }
        });
    }

    private void winner() {
        gameOver = true;
        repaint();

        String winner = (currentPlayer == 1) ? "Rouge" : "Bleu";
        statusLabel.setText("Le joueur " + winner + " a gagné !");

        int choice = JOptionPane.showConfirmDialog(
            this,
            winner + " a gagné !\nVoulez-vous recommencer ?",
            "Fin de partie",
            JOptionPane.YES_NO_OPTION
        );

        if (choice == JOptionPane.YES_OPTION) resetGame();
        else System.exit(0);
    }

    private void resetGame() {
        board = new int[N][N];
        currentPlayer = 1;
        gameOver = false;
        updateStatusLabel();
        repaint();
    }

    private void updateStatusLabel() {
        String player = (currentPlayer == 1) ? "Rouge" : "Bleu";
        statusLabel.setText("Au tour du joueur " + player);
    }

    @Override
    protected void paintComponent(Graphics g) {
        super.paintComponent(g);

        double stepX = 1.5 * SIZE;
        double stepY = Math.sqrt(3) * SIZE / 2.0;

        int boardWidth = (int) ((2 * (N - 1)) * stepX + 2 * SIZE);
        int originX = (getWidth() - boardWidth) / 2 + SIZE;
        int originY = getHeight() / 2;

        for (int row = 0; row < N; row++) {
            for (int col = 0; col < N; col++) {
                int x = (int) (originX + (col + row) * stepX);
                int y = (int) (originY + (col - row) * stepY);

                Polygon hex = createHexagon(x, y, SIZE - 2);
                HEXS[row][col] = hex;

                Color fillColor = getFillColor(row, col);

                if (board[row][col] == 1) fillColor = Color.RED;
                else if (board[row][col] == 2) fillColor = Color.BLUE;

                if (fillColor != null) {
                    g.setColor(fillColor);
                    g.fillPolygon(hex);
                }
            }
        }

        g.setColor(Color.BLACK);

        for (int row = 1; row < N - 1; row++) {
            for (int col = 1; col < N - 1; col++) {
                g.drawPolygon(HEXS[row][col]);
            }
        }
    }

    private Color getFillColor(int row, int col) {
        boolean top = row == 0;
        boolean bottom = row == N - 1;
        boolean left = col == 0;
        boolean right = col == 0 ? false : col == N - 1;

        boolean topLeftCorner = top && left;
        boolean topRightCorner = top && right;
        boolean bottomLeftCorner = bottom && left;
        boolean bottomRightCorner = bottom && right;

        if (topLeftCorner || topRightCorner || bottomLeftCorner || bottomRightCorner) return Color.BLACK;
        else if (right || left) return Color.RED;
        else if (top || bottom) return Color.BLUE;

        return null;
    }

    private Polygon createHexagon(int cx, int cy, int size) {
        int[] xPoints = new int[6];
        int[] yPoints = new int[6];

        for (int i = 0; i < 6; i++) {
            double angle = Math.toRadians(60 * i);
            xPoints[i] = (int) Math.round(cx + size * Math.cos(angle));
            yPoints[i] = (int) Math.round(cy + size * Math.sin(angle));
        }

        return new Polygon(xPoints, yPoints, 6);
    }

    private boolean hasWon(int player) {
        boolean[][] visited = new boolean[N][N];

        if (player == 1) {
            // RED line winner
            for (int row = 1; row < N - 1; row++) {
                if (board[row][1] == 1 && dfs(row, 1, player, visited)) return true;
            }
        } else {
            // BLUE line winner
            for (int col = 1; col < N - 1; col++) {
                if (board[1][col] == 2 && dfs(1, col, player, visited)) return true;
            }
        }

        return false;
    }

    private boolean dfs(int row, int col, int player, boolean[][] visited) {
        if (row < 1 || row >= N - 1 || col < 1 || col >= N - 1) return false;
        if (visited[row][col]) return false;
        if (board[row][col] != player) return false;

        if (player == 1 && col == N - 2) return true;
        if (player == 2 && row == N - 2) return true;

        visited[row][col] = true;

        int[][] directions = {
            {-1, 0}, {-1, 1},
            {0, -1}, {0, 1},
            {1, -1}, {1, 0}
        };

        for (int[] dir : directions) {
            if (dfs(row + dir[0], col + dir[1], player, visited)) return true;
        }

        return false;
    }

    public static void main(String[] args) {
        JFrame frame = new JFrame("Hex Board");
        frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        frame.setExtendedState(JFrame.MAXIMIZED_BOTH);
        frame.setLayout(new BorderLayout());

        JLabel statusLabel = new JLabel("Au tour du joueur Rouge", SwingConstants.CENTER);
        statusLabel.setFont(new Font("Arial", Font.BOLD, 20));
        statusLabel.setBorder(BorderFactory.createEmptyBorder(10, 10, 10, 10));
        statusLabel.setOpaque(true);
        statusLabel.setForeground(Color.BLACK);

        Main gamePanel = new Main(statusLabel);

        frame.add(gamePanel, BorderLayout.CENTER);
        frame.add(statusLabel, BorderLayout.SOUTH);

        frame.setVisible(true);
    }
}