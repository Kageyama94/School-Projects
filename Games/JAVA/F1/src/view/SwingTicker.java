package view;

import javax.swing.Timer;

import model.game.Ticker;

public class SwingTicker implements Ticker {
    private final Timer timer;
 
    public SwingTicker(int millis, Runnable onTick) { this.timer = new Timer(millis, _ -> onTick.run()); }
 
    @Override public void start() { timer.start(); }
    @Override public void stop()  { timer.stop();  }
}
