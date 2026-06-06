package model.car.policy;

import java.util.Optional;

import model.GameConfig;
import model.car.Car;

public class HybridSystem implements Policy, EnergySource {
    private final Movement movement = new Movement();
    private int battery = GameConfig.Battery.MAX;

    @Override
    public int rollSteps(Car.State state) {
        int steps = movement.rollSteps(state);
        if (steps == 0) return 0;
        if (battery > 0) battery = Math.max(0, battery - GameConfig.Battery.CONSUMPTION);
        return steps;
    }

    @Override public Optional<EnergySource> energySource() { return Optional.of(this); }
    @Override public int getLevel() { return battery; }
    @Override public void setLevel(int level) { battery = Math.max(0, Math.min(GameConfig.Battery.MAX, level)); }
    @Override public void recharge() { setLevel(battery + GameConfig.Battery.RECHARGE); }
}