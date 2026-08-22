package view;

import java.awt.*;
import javax.swing.JPanel;

import model.TronModel;

public class BoardPanel extends JPanel {
    private static final Color COLOR_VOID = Color.GRAY;
    private static final Color COLOR_P1 = Color.RED;
    private static final Color COLOR_P2 = Color.BLUE;
    private static final Color COLOR_OBSTACLE = Color.BLACK;
    private static final Color COLOR_GRID_LINE = Color.DARK_GRAY;
    private static final Color COLOR_HEAD = Color.WHITE;
    private static final Color COLOR_CRASH_MARK = Color.BLACK;

    private final TronModel model;
    private final int cell = 25;

    public BoardPanel(TronModel model) {
        this.model = model;
        setPreferredSize(new Dimension(
                model.getCol() * cell,
                model.getRow() * cell
        ));
        setBackground(COLOR_VOID);
    }

    @Override
    protected void paintComponent(Graphics g) {
        super.paintComponent(g);

        int[][] grid = model.getGrid();
        for (int i = 0; i < model.getRow(); i++) {
            for (int j = 0; j < model.getCol(); j++) {
                switch (grid[i][j]) {
                    case TronModel.VOID:
                        g.setColor(COLOR_VOID);
                        break;
                    case TronModel.TRACE_P1:
                        g.setColor(COLOR_P1);
                        break;
                    case TronModel.TRACE_P2:
                        g.setColor(COLOR_P2);
                        break;
                    case TronModel.OBSTACLE:
                        g.setColor(COLOR_OBSTACLE);
                        break;
                }
                g.fillRect(j * cell, i * cell, cell, cell);
                g.setColor(COLOR_GRID_LINE);
                g.drawRect(j * cell, i * cell, cell, cell);
            }
        }

        drawHead(g, model.getP1C(), model.getP1R(), model.isP1Alive());
        drawHead(g, model.getP2C(), model.getP2R(), model.isP2Alive());
    }

    private void drawHead(Graphics g, int col, int row, boolean alive) {
        int x = col * cell;
        int y = row * cell;

        g.setColor(COLOR_HEAD);
        g.fillOval(x, y, cell, cell);

        if (!alive) {
            g.setColor(COLOR_CRASH_MARK);
            g.drawLine(x + 4, y + 4, x + cell - 4, y + cell - 4);
            g.drawLine(x + cell - 4, y + 4, x + 4, y + cell - 4);
        }
    }
}
