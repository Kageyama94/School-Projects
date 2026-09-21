package controller;

import model.TronModel;
import view.TronView;

import javax.swing.*;
import java.awt.event.KeyAdapter;
import java.awt.event.KeyEvent;

public class TronController {
    private static final int INITIAL_DELAY = 500;
    private static final int MIN_DELAY = 150;
    private static final int SPEEDUP_STEP = 30;
    private static final int TICKS_PER_SPEEDUP = 15;

    private static final int P1_UP = KeyEvent.VK_Z;
    private static final int P1_DOWN = KeyEvent.VK_S;
    private static final int P1_LEFT = KeyEvent.VK_Q;
    private static final int P1_RIGHT = KeyEvent.VK_D;
    private static final int P2_UP = KeyEvent.VK_UP;
    private static final int P2_DOWN = KeyEvent.VK_DOWN;
    private static final int P2_LEFT = KeyEvent.VK_LEFT;
    private static final int P2_RIGHT = KeyEvent.VK_RIGHT;

    private final TronModel model;
    private final TronView view;
    private final GameMode mode;
    private Timer timer;
    private int tickCount = 0;

    public TronController(TronModel model, TronView view, GameMode mode) {
        this.model = model;
        this.view = view;
        this.mode = mode;

        if (mode != GameMode.EVE) touch();
        loop();
    }

    private void touch() {
        view.addKeyListener(new KeyAdapter() {
            @Override
            public void keyPressed(KeyEvent e) {
                int dr = 0;
                int dc = 0;
                boolean j1 = false;
                boolean j2 = false;

                switch (e.getKeyCode()) {
                    case P1_UP:
                        dr = -1; j1 = true; break;
                    case P1_DOWN:
                        dr = 1; j1 = true; break;
                    case P1_LEFT:
                        dc = -1; j1 = true; break;
                    case P1_RIGHT:
                        dc = 1; j1 = true; break;
                    case P2_UP:
                        dr = -1; j2 = true; break;
                    case P2_DOWN:
                        dr = 1; j2 = true; break;
                    case P2_LEFT:
                        dc = -1; j2 = true; break;
                    case P2_RIGHT:
                        dc = 1; j2 = true; break;
                    default:
                        return;
                }

                if (j1) model.changeDirection(1, dr, dc);
                if (mode == GameMode.PVP && j2) model.changeDirection(2, dr, dc);
            }
        });
        view.setFocusable(true);
        view.requestFocusInWindow();
    }

    private void loop() {
        timer = new Timer(INITIAL_DELAY, e -> {
            tickCount++;
            if (tickCount % TICKS_PER_SPEEDUP == 0) {
                timer.setDelay(Math.max(MIN_DELAY, timer.getDelay() - SPEEDUP_STEP));
            }

            boolean[] result;
            if (mode == GameMode.PVP) result = model.step(false, false, false, false);
            else if (mode == GameMode.PVE) result = model.step(false, false, true, true);
            else result = model.step(true, false, true, false);

            boolean alive1 = result[0];
            boolean alive2 = result[1];

            if (!alive1 && !alive2) model.setGameOver("Égalité !");
            else if (!alive1) model.setGameOver("Le joueur BLEU gagne !");
            else if (!alive2) model.setGameOver("Le joueur ROUGE gagne !");

            view.refresh();

            if (model.isGameOver()) {
                timer.stop();
                if (view.askReplay(model.getMessageFin())) restart();
            }
        });
        timer.start();
    }

    private void restart() {
        model.initialise();
        tickCount = 0;
        timer.setDelay(INITIAL_DELAY);
        view.refresh();
        if (mode != GameMode.EVE) view.requestFocusInWindow();
        timer.start();
    }
}
