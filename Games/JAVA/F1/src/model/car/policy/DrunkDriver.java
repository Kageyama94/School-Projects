package model.car.policy;

import model.car.Car;

public class DrunkDriver implements Policy {
    private final Movement movement = new Movement();
    private boolean forward = true;

    @Override
    public int rollSteps(Car.State state) {
        int steps = movement.rollSteps(state);
        if (!forward) steps = -steps;
        forward = !forward;
        return steps;
    }
}