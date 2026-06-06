package view;

import javax.swing.*;

import controller.GameController;
import model.game.*;
import model.observer.*;

public class ControlPanel extends JPanel implements ModelListener {
    private final GameController controller;
    private final JButton pause = new JButton("Pause");
    private final JButton resume = new JButton("Reprendre");
    private final JButton bwd = new JButton("Reculer");
    private final JButton fwd = new JButton("Avancer");

    public ControlPanel(GameController controller) {
        this.controller = controller;

        add(pause);
        add(resume);
        add(bwd);
        add(fwd);

        pause.addActionListener(_ -> controller.pause());
        resume.addActionListener(_ -> controller.resume());
        bwd.addActionListener(_ -> controller.step(-1));
        fwd.addActionListener(_ -> controller.step(+1));

        controller.getGame().addListener(this);
    }

    @Override
    public void onModelEvent(ModelEvent e) {
        switch (e.type()) {
            case STATE_CHANGED, FINISHED, TICK -> refresh();
            default -> {}
        }
    }

    private void refresh() {
        Game g = controller.getGame();
        Game.State s = g.getState();

        boolean running  = (s == Game.State.RUNNING);
        boolean paused   = (s == Game.State.PAUSED);
        boolean finished = (s == Game.State.FINISHED);

        pause.setVisible(running);
        resume.setVisible(paused);

        if (finished) {
            int ptr  = g.getHistoryPtr();
            int size = g.getHistorySize();
            bwd.setVisible(ptr > 0);
            fwd.setVisible(ptr >= 0 && ptr < size - 1);
        } else {
            bwd.setVisible(false);
            fwd.setVisible(false);
        }
    }
}