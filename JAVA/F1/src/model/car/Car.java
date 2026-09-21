package model.car;

import java.awt.Color;

import model.GameConfig;
import model.car.policy.*;
import model.observer.AbstractObservableModel;
import model.observer.ModelEvent;
import model.track.Track;


public class Car extends AbstractObservableModel {
    public enum State { STOPPED, LOW, NORMAL, BOOST, DAMAGED }
    private final Color color;
    private final Track track;
    private final Policy policy;
    private final EnergySource energy;

    private State state = State.NORMAL;
    private int damageTurnsLeft = 0;
    private int fuel = GameConfig.Car.INITIAL_FUEL;
    private int laps = 0;
    private int roadIndex;
    private boolean lastTickLapped = false;

    public Car(Color color, Track track, Policy policy) {
        this.color = color;
        this.track = track;
        this.policy = policy;
        this.energy = policy.energySource().orElse(null);
        this.roadIndex = track.getStartIndex();
    }

    public int computeSteps() {
        if (state == State.DAMAGED) {
            tickDamage();
            return 0;
        }
        return policy.rollSteps(state);
    }

    private void tickDamage() {
        if (damageTurnsLeft <= 0) return;
        damageTurnsLeft--;
        if (damageTurnsLeft == 0) setState(State.LOW);
    }

    public void advance(int steps) {
        lastTickLapped = false;
        int before = roadIndex;

        roadIndex = track.advanceOnRoad(roadIndex, steps);
        if (steps > 0 && track.crossedFinish(before, roadIndex)) {
            lastTickLapped = true;
            laps++;
        } else if (steps < 0 && track.crossedFinish(roadIndex, before)) {
            lastTickLapped = true;
            laps--;
        }

        if (roadIndex != before) fire(ModelEvent.Type.POSITION_CHANGED);
        if (lastTickLapped) fire(ModelEvent.Type.LAP_CHANGED);
    }

    public void setState(State newState) {
        this.state = newState;
        this.damageTurnsLeft = (newState == State.DAMAGED) ? GameConfig.Car.DAMAGE_DURATION : 0;
        fire(ModelEvent.Type.STATE_CHANGED);
    }

    public void restoreState(int roadIndex, int fuel, int laps, State state, int damageTurnsLeft, int battery) {
        this.roadIndex = roadIndex;
        this.fuel = fuel;
        this.laps = laps;
        this.state = state;
        this.damageTurnsLeft = damageTurnsLeft;
        this.lastTickLapped = false;
        if (energy != null && battery >= 0) energy.setLevel(battery);
    }

    public boolean isOnBattery() { return energy != null && energy.isActive(); }
    public int getBatteryLevel() { return energy != null ? energy.getLevel() : -1; }
    public void brake() {
        if (energy != null) {
            energy.recharge();
            fire(ModelEvent.Type.FUEL_CHANGED);
        }
    }

    public void consumeFuel(int amount) {
        fuel = Math.max(0, fuel - amount);
        fire(ModelEvent.Type.FUEL_CHANGED);
    }
    public boolean isOutOfFuel() { return fuel <= 0 && !isOnBattery(); }

    public Color getColor() { return color; }
    public Track getTrack() { return track; }
    public int getFuel() { return fuel; }
    public int getLaps() { return laps; }
    public int getRoadIndex() { return roadIndex; }
    public State getState() { return state; }
    public int getDamageTurnsLeft() { return damageTurnsLeft; }
}