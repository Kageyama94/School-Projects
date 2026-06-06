package model.game;

import model.GameConfig;
import model.car.Car;
import model.track.Track;

public class TickProcessor {
    private final Track track;
 
    public TickProcessor(Track track) { this.track = track; }
 
    public void stepCar(Car c) {
        updateStateFromFuel(c);
        boolean onBattery = c.isOnBattery();
 
        int before = c.getRoadIndex();
        int steps = c.computeSteps();
        if (steps == 0) return;
 
        c.advance(steps);
        if (!onBattery) consumeFuel(c);
        if (track.isDamaged(before, steps)) c.setState(Car.State.DAMAGED);
    }
 
    private void updateStateFromFuel(Car c) {
        if (c.isOnBattery()) return;
        int fuel = c.getFuel();
        Car.State s = c.getState();
        if (fuel < GameConfig.Fuel.CONSUMPTION_BOOST && s == Car.State.BOOST) c.setState(Car.State.NORMAL);
        else if (fuel < GameConfig.Fuel.CONSUMPTION_NORMAL && s == Car.State.NORMAL) c.setState(Car.State.LOW);
    }
 
    private void consumeFuel(Car c) {
        int amount = switch (c.getState()) {
            case LOW -> GameConfig.Fuel.CONSUMPTION_LOW;
            case BOOST -> GameConfig.Fuel.CONSUMPTION_BOOST;
            default -> GameConfig.Fuel.CONSUMPTION_NORMAL;
        };
        c.consumeFuel(amount);
    }
}