package model.car.policy;

import model.car.Car;

public class SoundBooster extends PolicyDecorator {
    private final Sound sound;

    public SoundBooster(Sound sound) { this.sound = sound; }

    @Override
    public int rollSteps(Car.State state) {
        int steps = movement.rollSteps(state);
        if (steps != 0) sound.play();
        return steps;
    }
}