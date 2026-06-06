package model.game;

import java.util.*;

import model.car.Car;
import model.observer.ModelEvent;

public class History {
    private final List<Map<String, CarSnapshot>> snapshots = new ArrayList<>();
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
        Map<String, CarSnapshot> snap = new HashMap<>();
        for (Player p : game.getPlayers()) snap.put(p.getName(), CarSnapshot.of(p.getCar()));
        snapshots.add(snap);
        ticksHistory.add(game.getTicks());
        historyPtr = snapshots.size() - 1;
    }

    private void apply(Game game, int idx) {
        Map<String, CarSnapshot> snap = snapshots.get(idx);
        for (Player p : game.getPlayers()) {
            CarSnapshot s = snap.get(p.getName());
            if (s == null) continue;
            p.getCar().restoreState(
                s.roadIndex(), s.fuel(), s.laps(),
                s.state(), s.damage(), s.battery()
            );
        }
        game.restoreTicks(ticksHistory.get(idx));
        game.notifyListeners(new ModelEvent(ModelEvent.Type.TICK));
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