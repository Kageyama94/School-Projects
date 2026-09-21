package model.game;

import java.util.*;

import model.GameConfig;
import model.car.Car;
import model.observer.ModelEvent;

public class History {
    private final List<List<CarSnapshot>> snapshots = new ArrayList<>();
    private final List<Integer> ticksHistory = new ArrayList<>();
    private int historyPtr = -1;

    private record CarSnapshot(int roadIndex, int fuel, int battery, int laps, Car.State state, int damage) {
        static CarSnapshot of(Car c) {
            return new CarSnapshot(
                c.getRoadIndex(), c.getFuel(), c.getBatteryLevel(),
                c.getLaps(), c.getState(), c.getDamageTurnsLeft()
            );
        }
    }

    public void record(Game game) {
        List<CarSnapshot> snap = game.getPlayers().stream().map(p -> CarSnapshot.of(p.getCar())).toList();
        snapshots.add(snap);
        ticksHistory.add(game.getTicks());
        historyPtr = snapshots.size() - 1;

        if (snapshots.size() > GameConfig.History.MAX_SNAPSHOTS) {
            snapshots.remove(0);
            ticksHistory.remove(0);
            historyPtr--;
        }
    }

    private void apply(Game game, int idx) {
        List<CarSnapshot> snap = snapshots.get(idx);
        List<Player> players = game.getPlayers();
        for (int i = 0; i < players.size(); i++) {
            CarSnapshot s = snap.get(i);
            players.get(i).getCar().restoreState(
                s.roadIndex(), s.fuel(), s.laps(),
                s.state(), s.damage(), s.battery()
            );
        }
        game.restoreTicks(ticksHistory.get(idx));
        game.notifyListeners(new ModelEvent(ModelEvent.Type.TICK));
    }

    public void restoreToTip(Game game) {
        if (!snapshots.isEmpty()) apply(game, snapshots.size() - 1);
    }

    private boolean canReplay(Game game) {
        Game.State gs = game.getState();
        return gs == Game.State.PAUSED || gs == Game.State.FINISHED;
    }

    public void step(Game game, int delta) {
        int next = historyPtr + delta;
        if (!canReplay(game) || next < 0 || next >= snapshots.size()) return;
        apply(game, historyPtr = next);
    }

    public boolean isEmpty() { return snapshots.isEmpty(); }
    public int size() { return snapshots.size(); }
    public int getPtr() { return historyPtr; }
}