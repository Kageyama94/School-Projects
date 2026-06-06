package view;

import java.awt.*;
import javax.swing.JPanel;

import model.TronModel;

public class BoardPanel extends JPanel {
    private final TronModel model;
    private final int cell = 25;

    public BoardPanel(TronModel model) {
        this.model = model;
        setPreferredSize(new Dimension(
                model.getCol() * cell,
                model.getRow() * cell
        ));
        setBackground(Color.GRAY);
    }

    @Override
    protected void paintComponent(Graphics g) {
        super.paintComponent(g);

        int[][] grid = model.getGrid();
        for (int i = 0; i < model.getRow(); i++) {
            for (int j = 0; j < model.getCol(); j++) {
                switch (grid[i][j]) {
                    case TronModel.VOID:
                        g.setColor(Color.GRAY);
                        break;
                    case TronModel.TRACE_P1:
                        g.setColor(Color.RED);
                        break;
                    case TronModel.TRACE_P2:
                        g.setColor(Color.BLUE);
                        break;
                    case TronModel.OBSTACLE:
                        g.setColor(Color.BLACK);
                        break;
                }
                g.fillRect(j * cell, i * cell, cell, cell);
                g.setColor(Color.DARK_GRAY);
                g.drawRect(j * cell, i * cell, cell, cell);
            }
        }

        g.setColor(Color.WHITE);
        if (model.isP1Alive()) g.fillOval(model.getP1C() * cell, model.getP1R() * cell, cell, cell);
        if (model.isP2Alive()) g.fillOval(model.getP2C() * cell, model.getP2R() * cell, cell, cell);
    }
}
