package model.game;

import model.GameConfig;
import model.car.Car;
import model.observer.*;
import model.track.Track;

import java.util.Comparator;
import java.util.List;
import java.util.function.Function;

public class Game extends AbstractObservableModel {
    public enum State { RUNNING, PAUSED, FINISHED }
    private final Track track;
    private final List<Player> players;
    private final Ticker ticker;
    private final TickProcessor processor;
    private final History history = new History();
    private final ModelListener carRelay = this::notifyListeners;
    private State state = State.PAUSED;
    private int ticks = 0;

    public Game(Track track, List<Player> players, Function<Runnable, Ticker> tickerFactory) {
        this.track = track;
        this.players = List.copyOf(players);
        this.processor = new TickProcessor(track);
        this.ticker = tickerFactory.apply(this::tick);

        for (Player p : players) p.getCar().addListener(carRelay);
    }

    private void tick() {
        ticks++;
        playAll();
        history.record(this);
        fire(ModelEvent.Type.TICK);

        if (hasWinner() || allOutOfFuel()) finish();
        else if (allStopped()) pause();
    }

    private void playAll() {
        for (Player p : players) {
            Car c = p.getCar();
            if (c.isOutOfFuel()) continue;
            processor.stepCar(c);
        }
    }

    private boolean allOutOfFuel() { return players.stream().allMatch(p -> p.getCar().isOutOfFuel()); }

    private boolean allStopped() {
        return players.stream()
            .map(Player::getCar)
            .filter(c -> !c.isOutOfFuel())
            .allMatch(c -> c.getState() == Car.State.STOPPED);
    }

    private boolean hasWinner() {
        for (Player p : players) {
            if (p.getCar().getLaps() >= GameConfig.Game.LAPS_TO_WIN) return true;
        }
        return false;
    }

    public void pause() {
        if (state == State.FINISHED) return;
        setState(State.PAUSED);
    }

    public void resume() {
        if (state != State.PAUSED) return;
        setState(State.RUNNING);
    }

    private void finish() { setState(State.FINISHED); }

    private void setState(State newState) {
        if (newState == State.RUNNING && history.isEmpty()) history.record(this);
        state = newState;
        if (newState == State.RUNNING) ticker.start(); else ticker.stop();
        fire(newState == State.FINISHED ? ModelEvent.Type.FINISHED
                                            : ModelEvent.Type.STATE_CHANGED);
    }

    public void step(int delta) { history.step(this, delta); }

    public List<Player> getRanking() {
        return players.stream()
            .sorted(Comparator
                .comparingInt((Player p) -> p.getCar().getLaps()).reversed()
                .thenComparing(Comparator.comparingInt((Player p) -> p.getCar().getRoadIndex()).reversed()))
            .toList();
    }

    public int getTicks() { return ticks; }
    public void restoreTicks(int t){ this.ticks = t; }
    public Track getTrack() { return track; }
    public List<Player> getPlayers() { return players; }
    public State getState() { return state; }
    public int getHistoryPtr() { return history.getPtr(); }
    public int getHistorySize() { return history.size(); }
}