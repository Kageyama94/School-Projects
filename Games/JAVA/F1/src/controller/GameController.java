package controller;

import model.car.Car;
import model.game.*;

public class GameController {
    public enum ChangeSpeedError { NOT_RUNNING, DAMAGED, ALREADY_BOOSTED, ALREADY_STOPPED }
    private final Game game;

    public GameController(Game game) { this.game = game; }

    public ChangeSpeedError changeSpeed(Player player, int delta) {
        if (game.getState() != Game.State.RUNNING) return ChangeSpeedError.NOT_RUNNING;

        Car car = player.getCar();
        Car.State state = car.getState();
        if (state == Car.State.DAMAGED) return ChangeSpeedError.DAMAGED;
        if (delta > 0 && state == Car.State.BOOST) return ChangeSpeedError.ALREADY_BOOSTED;
        if (delta < 0 && state == Car.State.STOPPED) return ChangeSpeedError.ALREADY_STOPPED;

        Car.State next = nextState(state, delta);
        if (next == null) return null;
        car.setState(next);
        if (delta < 0) car.brake();
        return null;
    }

    private static Car.State nextState(Car.State current, int delta) {
        if (delta == 0) return null;
        boolean up = delta > 0;
        return switch (current) {
            case DAMAGED -> null;
            case STOPPED -> up ? Car.State.LOW : null;
            case LOW -> up ? Car.State.NORMAL : Car.State.STOPPED;
            case NORMAL -> up ? Car.State.BOOST : Car.State.LOW;
            case BOOST -> up ? null : Car.State.NORMAL;
        };
    }

    public void pause() { game.pause(); }
    public void resume() { game.resume(); }
    public void step(int delta) { game.step(delta); }
    public Game getGame() { return game; }
}