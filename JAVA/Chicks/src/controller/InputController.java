package controller;

import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;

import javax.swing.SwingUtilities;

import model.SelectionModel;
import model.characters.GameConfig;

public class InputController extends MouseAdapter {
    private static final int CELL = GameConfig.CELL_SIZE;

    private final GameController gameController;
    private final SelectionModel selectionModel;

    public InputController(GameController gameController, SelectionModel selectionModel) {
        this.gameController = gameController;
        this.selectionModel = selectionModel;
    }

    @Override
    public void mouseMoved(MouseEvent e) {
        gameController.onMouseMoved(e.getY() / CELL, e.getX() / CELL);
    }

    @Override
    public void mouseClicked(MouseEvent e) {
        if (SwingUtilities.isLeftMouseButton(e)) {
            gameController.onMouseClicked(
                e.getY() / CELL, e.getX() / CELL,
                selectionModel.getSelected()
            );
        }
    }

    @Override
    public void mouseExited(MouseEvent e) {
        gameController.onMouseExited();
    }
}
