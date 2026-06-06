package view;

import java.awt.*;

import model.GameState;
import model.characters.GameConfig;
import model.environment.Obstacle;
import model.environment.Portal;

public class Renderer {
    private static final int CELL = GameConfig.CELL_SIZE;

    public void render(Graphics g, GameState state, Obstacle obstacle, Portal portal,
                       int activeRow, int activeCol,
                       int panelWidth, int panelHeight) {
        if (state.gameEnded()) drawEndMessage(g, state.win(), panelWidth, panelHeight);
        else {
            drawEnvironment(g, obstacle, portal);
            drawCharacters(g, state);
        }
        drawStats(g, state);
        if (activeRow != -1 && activeCol != -1) drawCellCursor(g, activeRow, activeCol);
    }

    private void drawEnvironment(Graphics g, Obstacle obstacle, Portal portal) {
        portal.draw(g);
        obstacle.draw(g);
    }

    private void drawCharacters(Graphics g, GameState state) {
        for (GameState.CharacterSnapshot snap : state.snapshots()) {
            Color color = switch (snap.activeTask()) {
                case BLOQUEUR -> new Color(0x1a, 0x1a, 0x4e);
                case PARACHUTISTE -> Color.CYAN;
                case BOMBEUR -> Color.ORANGE;
                case null, default -> Color.YELLOW;
            };
            g.setColor(color);
            g.fillOval(snap.x(), snap.y(), CELL, CELL);
        }
    }

    private void drawStats(Graphics g, GameState state) {
        g.setColor(Color.BLACK);
        g.setFont(new Font("Arial", Font.BOLD, 16));
        g.drawString("Total des poussins : " + state.totalCharacters(), 10, 20);
        g.drawString("Poussins visibles : " + state.visibleCharacters(), 10, 40);
        g.drawString("Poussins sorties : " + state.charactersAtExit(), 10, 60);
        g.drawString("Poussins morts : " + state.deadCharacters(), 10, 80);
    }

    public void drawCellCursor(Graphics g, int row, int col) {
        int x = col * CELL, y = row * CELL;
        g.setColor(Color.BLACK);
        for (int i = 0; i < 2; i++) {
            int o = (i == 0) ? 0 : CELL - 10;
            g.drawLine(x + o, y, x + o + 10, y);
            g.drawLine(x + o, y + CELL, x + o + 10, y + CELL);
            g.drawLine(x, y + o, x, y + o + 10);
            g.drawLine(x + CELL, y + o, x + CELL, y + o + 10);
        }
    }

    private void drawEndMessage(Graphics g, boolean win, int w, int h) {
        String msg = win
            ? "Bravo ! Les poussins ont été sauvés !"
            : "Game Over, aucun poussin n'a été sauvé !";
        g.setColor(Color.BLACK);
        g.fillRect(0, 0, w, h);
        g.setColor(win ? Color.WHITE : Color.RED);
        g.setFont(new Font("Arial", Font.BOLD, 20));
        FontMetrics fm = g.getFontMetrics();
        g.drawString(msg, (w - fm.stringWidth(msg)) / 2,
                          (h - fm.getHeight()) / 2 + fm.getAscent());
    }
}