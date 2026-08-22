import javax.swing.*;
import java.awt.*;
import java.awt.event.*;
import java.util.ArrayList;
import java.util.List;

public class Main extends JPanel {
    private static final int DEFAULT_PLAYABLE_SIZE = 9;
    private static final int MIN_PLAYABLE_SIZE = 3;
    private static final int MAX_PLAYABLE_SIZE = 25;

    private final int N;

    private int hexSize = 30;
    private int cachedWidth = -1;
    private int cachedHeight = -1;

    private Polygon[][] HEXS;
    private int[][] board;
    private int currentPlayer = 1;
    private boolean gameOver = false;
    private JLabel statusLabel;
    private List<int[]> winningPath = new ArrayList<>();

    public Main(JLabel statusLabel, int boardSize) {
        this.N = boardSize;
        this.HEXS = new Polygon[N][N];
        this.board = new int[N][N]; // 0 = VOID, 1 = RED, 2 = BLUE
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
        else if (choice == JOptionPane.NO_OPTION) System.exit(0);
    }

    private void resetGame() {
        board = new int[N][N];
        currentPlayer = 1;
        gameOver = false;
        winningPath.clear();
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

        if (getWidth() != cachedWidth || getHeight() != cachedHeight) {
            recomputeLayout();
            cachedWidth = getWidth();
            cachedHeight = getHeight();
        }

        for (int row = 0; row < N; row++) {
            for (int col = 0; col < N; col++) {
                Polygon hex = HEXS[row][col];
                if (hex == null) continue;

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

        if (gameOver && !winningPath.isEmpty()) {
            Graphics2D g2 = (Graphics2D) g;
            Stroke oldStroke = g2.getStroke();
            g2.setStroke(new BasicStroke(4));
            g2.setColor(Color.YELLOW);

            for (int[] cell : winningPath) {
                g2.drawPolygon(HEXS[cell[0]][cell[1]]);
            }

            g2.setStroke(oldStroke);
        }
    }

    private void recomputeLayout() {
        hexSize = computeHexSize();

        double stepX = 1.5 * hexSize;
        double stepY = Math.sqrt(3) * hexSize / 2.0;

        int boardWidth = (int) ((2 * (N - 1)) * stepX + 2 * hexSize);
        int originX = (getWidth() - boardWidth) / 2 + hexSize;
        int originY = getHeight() / 2;

        for (int row = 0; row < N; row++) {
            for (int col = 0; col < N; col++) {
                int x = (int) (originX + (col + row) * stepX);
                int y = (int) (originY + (col - row) * stepY);
                HEXS[row][col] = createHexagon(x, y, hexSize - 2);
            }
        }
    }

    private int computeHexSize() {
        int w = getWidth();
        int h = getHeight();
        if (w <= 0 || h <= 0) return 30;

        int widthBasedSize = (int) (w / (3.0 * (N - 1) + 2) * 0.95);
        int heightBasedSize = (int) (h / (Math.sqrt(3) * (N - 1) + 2) * 0.9);

        return Math.max(12, Math.min(widthBasedSize, heightBasedSize));
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
        List<int[]> path = new ArrayList<>();

        if (player == 1) {
            // RED line winner
            for (int row = 1; row < N - 1; row++) {
                if (board[row][1] == 1) {
                    path.clear();
                    if (dfs(row, 1, player, visited, path)) {
                        winningPath = new ArrayList<>(path);
                        return true;
                    }
                }
            }
        } else {
            // BLUE line winner
            for (int col = 1; col < N - 1; col++) {
                if (board[1][col] == 2) {
                    path.clear();
                    if (dfs(1, col, player, visited, path)) {
                        winningPath = new ArrayList<>(path);
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private boolean dfs(int row, int col, int player, boolean[][] visited, List<int[]> path) {
        if (row < 1 || row >= N - 1 || col < 1 || col >= N - 1) return false;
        if (visited[row][col]) return false;
        if (board[row][col] != player) return false;

        visited[row][col] = true;
        path.add(new int[]{row, col});

        if (player == 1 && col == N - 2) return true;
        if (player == 2 && row == N - 2) return true;

        int[][] directions = {
            {-1, 0}, {-1, 1},
            {0, -1}, {0, 1},
            {1, -1}, {1, 0}
        };

        for (int[] dir : directions) {
            if (dfs(row + dir[0], col + dir[1], player, visited, path)) return true;
        }

        path.remove(path.size() - 1);
        return false;
    }

    private static int parseBoardSize(String[] args) {
        int size = DEFAULT_PLAYABLE_SIZE;

        if (args.length > 0) {
            try {
                size = Integer.parseInt(args[0]);
            } catch (NumberFormatException e) {
                size = DEFAULT_PLAYABLE_SIZE;
            }
        }

        if (size < MIN_PLAYABLE_SIZE) size = MIN_PLAYABLE_SIZE;
        if (size > MAX_PLAYABLE_SIZE) size = MAX_PLAYABLE_SIZE;

        return size + 2;
    }

    public static void main(String[] args) {
        int boardSize = parseBoardSize(args);

        SwingUtilities.invokeLater(() -> {
            JFrame frame = new JFrame("Hex Board");
            frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
            frame.setExtendedState(JFrame.MAXIMIZED_BOTH);
            frame.setLayout(new BorderLayout());

            JLabel statusLabel = new JLabel("Au tour du joueur Rouge", SwingConstants.CENTER);
            statusLabel.setFont(new Font("Arial", Font.BOLD, 20));
            statusLabel.setBorder(BorderFactory.createEmptyBorder(10, 10, 10, 10));
            statusLabel.setOpaque(true);
            statusLabel.setForeground(Color.BLACK);

            Main gamePanel = new Main(statusLabel, boardSize);

            frame.add(gamePanel, BorderLayout.CENTER);
            frame.add(statusLabel, BorderLayout.SOUTH);

            frame.setVisible(true);
        });
    }
}
