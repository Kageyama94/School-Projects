package controller;

import model.TronModel;
import view.TronView;

import javax.swing.*;
import java.awt.event.KeyAdapter;
import java.awt.event.KeyEvent;

public class TronController {
    private final TronModel model;
    private final TronView view;
    private final int mode;
    private Timer timer;

    public TronController(TronModel model, TronView view, int mode) {
        this.model = model;
        this.view = view;
        this.mode = mode;

        if (mode != 2) touch();
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
                    case KeyEvent.VK_Z:
                        dr = -1; j1 = true; break;
                    case KeyEvent.VK_S:
                        dr = 1; j1 = true; break;
                    case KeyEvent.VK_Q:
                        dc = -1; j1 = true; break;
                    case KeyEvent.VK_D:
                        dc = 1; j1 = true; break;
                    case KeyEvent.VK_UP:
                        dr = -1; j2 = true; break;
                    case KeyEvent.VK_DOWN:
                        dr = 1; j2 = true; break;
                    case KeyEvent.VK_LEFT:
                        dc = -1; j2 = true; break;
                    case KeyEvent.VK_RIGHT:
                        dc = 1; j2 = true; break;
                    default:
                        return;
                }

                if (j1) model.changeDirection(1, dr, dc);
                if (mode == 0 && j2) model.changeDirection(2, dr, dc);
            }
        });
        view.setFocusable(true);
        view.requestFocus();
    }

    private void loop() {
        timer = new Timer(500, e -> {
            boolean alive1 = true;
            boolean alive2 = true;

            if (mode == 0) {
                alive1 = model.advancePlayer(1);
                alive2 = model.advancePlayer(2);
            } else if (mode == 1) {
                alive1 = model.advancePlayer(1);
                alive2 = model.advanceIA(2, true);
            } else if (mode == 2) {
                alive1 = model.advanceIA(1, false);
                alive2 = model.advanceIA(2, false);
            }

            if (!alive1 && !alive2) model.setGameOver("Égalité !");
            else if (!alive1) model.setGameOver("Le joueur BLEU gagne !");
            else if (!alive2) model.setGameOver("Le joueur ROUGE gagne !");

            view.refresh();

            if (model.isGameOver()) {
                timer.stop();
                view.showEndMessage(model.getMessageFin());
            }
        });
        timer.start();
    }
}