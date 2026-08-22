package view;

import javax.swing.*;

import controller.GameController;
import model.game.Game;

import java.awt.*;

public class Frame extends JFrame {
    public Frame(Game game, GameController controller) {
        super("F1");
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        setLayout(new BorderLayout());

        add(gameWithOverlay(game, controller), BorderLayout.CENTER);

        JPanel bottom = new JPanel();
        bottom.setBackground(ViewConfig.GRASS);
        bottom.add(new Dashboard(game, controller));
        add(bottom, BorderLayout.SOUTH);

        pack();
        setLocationRelativeTo(null);
        setVisible(true);
    }

    private JPanel gameWithOverlay(Game game, GameController controller) {
        GamePanel gamePanel = new GamePanel(game);
        ControlPanel controls = new ControlPanel(controller);
        controls.setOpaque(false);
 
        gamePanel.setAlignmentY(Component.TOP_ALIGNMENT);
        controls.setAlignmentY(Component.TOP_ALIGNMENT);
 
        JPanel stack = new JPanel() {
            @Override public boolean isOptimizedDrawingEnabled() { return false; }
        };
        stack.setLayout(new OverlayLayout(stack));
        stack.add(controls);
        stack.add(gamePanel);
        return stack;
    }
}
