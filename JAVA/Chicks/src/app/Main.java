package app;

import java.awt.Color;
import java.awt.Dimension;
import java.awt.event.*;
import javax.swing.*;

import controller.*;
import model.SelectionModel;
import model.characters.GameConfig;
import model.characters.tasks.TaskType;
import view.GameView;

public class Main {
    public static void main(String[] args){
        SwingUtilities.invokeLater(() -> {
            JFrame frame = new JFrame("ChickApp");
            frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
            frame.setBackground(new Color(135, 206, 250));

            SelectionModel selectionModel = new SelectionModel();

            GameView gameView = new GameView();
            gameView.setPreferredSize(new Dimension(40 * GameConfig.CELL_SIZE, 21 * GameConfig.CELL_SIZE)); // 1200 × 630
            frame.setContentPane(gameView);

            JPopupMenu popup = new JPopupMenu();
            for (TaskType task : TaskType.values()) {
                JMenuItem item = new JMenuItem(task.name());
                item.addActionListener(e -> selectionModel.select(task));
                popup.add(item);
            }
            gameView.addMouseListener(new MouseAdapter() {
                public void mousePressed(MouseEvent e) {
                    if (e.isPopupTrigger()) popup.show(gameView, e.getX(), e.getY());
                }
                public void mouseReleased(MouseEvent e) {
                    if (e.isPopupTrigger()) popup.show(gameView, e.getX(), e.getY());
                }
            });

            frame.pack();
            frame.setResizable(false);
            frame.setLocationRelativeTo(null);
            frame.setVisible(true);
             
            GameController gameController = new GameController(gameView, frame);
            gameView.setInputController(new InputController(gameController, selectionModel));
            gameController.start();
        });
    }
}