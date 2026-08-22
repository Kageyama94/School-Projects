package model.car.policy;

import java.util.concurrent.ThreadLocalRandom;

import model.GameConfig;
import model.car.Car;

public class StandardDriving implements Policy {
    @Override
    public int rollSteps(Car.State state) {
        return switch (state) {
            case STOPPED, DAMAGED -> 0;
            case LOW -> roll(GameConfig.Movement.LOW_MIN, GameConfig.Movement.LOW_MAX); // 1..3
            case NORMAL -> roll(GameConfig.Movement.NORMAL_MIN, GameConfig.Movement.NORMAL_MAX); // 1..6
            case BOOST -> roll(GameConfig.Movement.BOOST_MIN, GameConfig.Movement.BOOST_MAX); // 5..10
        };
    }

    private static int roll(int min, int max) { return ThreadLocalRandom.current().nextInt(min, max + 1); }
}
