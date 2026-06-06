package view;

import javax.swing.JComponent;
import java.awt.Graphics;

import controller.InputController;
import model.GameState;
import model.environment.Obstacle;
import model.environment.Portal;

public class GameView extends JComponent {
    private Portal portal;
    private final Renderer renderer = new Renderer();

    private GameState lastState = null;
    private Obstacle obstacle   = null;
    private int activeRow = -1;
    private int activeCol = -1;

    private InputController inputController;

    public GameView() { setLayout(null); }

    public void setObstacle(Obstacle obstacle) { this.obstacle = obstacle; }
    public void setPortal(Portal portal) { this.portal = portal; }

    public void render(GameState state) {
        this.lastState = state;
        repaint();
    }

    public void setActiveCell(int row, int col) {
        this.activeRow = row;
        this.activeCol = col;
        repaint();
    }

    public void onGameEnd() {
        if (inputController != null) {
            removeMouseListener(inputController);
            removeMouseMotionListener(inputController);
        }
    }

    public void setInputController(InputController ic) {
        this.inputController = ic;
        addMouseListener(ic);
        addMouseMotionListener(ic);
    }

    @Override
    protected void paintComponent(Graphics g) {
        super.paintComponent(g);
        if (lastState == null || obstacle == null || portal == null) return;
        renderer.render(g, lastState, obstacle, portal, activeRow, activeCol, getWidth(), getHeight());
    }
}