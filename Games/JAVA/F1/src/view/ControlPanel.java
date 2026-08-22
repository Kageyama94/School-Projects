package view;

import javax.swing.*;

import controller.GameController;
import model.game.*;
import model.observer.*;

public class ControlPanel extends ObservingPanel {
    private final GameController controller;
    private final JButton pause = new JButton("Pause");
    private final JButton resume = new JButton("Reprendre");
    private final JButton bwd = new JButton("Reculer");
    private final JButton fwd = new JButton("Avancer");

    public ControlPanel(GameController controller) {
        super(ModelEvent.Type.STATE_CHANGED, ModelEvent.Type.FINISHED, ModelEvent.Type.TICK);
        this.controller = controller;

        add(bwd);
        add(pause);
        add(resume);
        add(fwd);

        pause.addActionListener(_ -> controller.pause());
        resume.addActionListener(_ -> controller.resume());
        bwd.addActionListener(_ -> controller.step(-1));
        fwd.addActionListener(_ -> controller.step(+1));

        controller.getGame().addListener(this);
        refresh();
    }

    @Override
    protected void refresh() {
        Game g = controller.getGame();
        Game.State s = g.getState();

        boolean running = (s == Game.State.RUNNING);
        boolean paused = (s == Game.State.PAUSED);
        boolean finished = (s == Game.State.FINISHED);

        pause.setVisible(running);
        resume.setVisible(paused);

        if (paused || finished) {
            int ptr = g.getHistoryPtr();
            int size = g.getHistorySize();
            bwd.setVisible(ptr > 0);
            fwd.setVisible(ptr >= 0 && ptr < size - 1);
        } else {
            bwd.setVisible(false);
            fwd.setVisible(false);
        }
    }
}