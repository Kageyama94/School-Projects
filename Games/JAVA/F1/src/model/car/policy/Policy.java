package model.car.policy;

import java.util.Optional;
import java.util.function.Supplier;

import model.car.Car;

public interface Policy {
    int rollSteps(Car.State state);
    default Optional<EnergySource> energySource() { return Optional.empty(); }

    static Policy build(CarOption option, Supplier<Sound> soundFactory) {
        if (option == null) return new Movement();
        return switch (option) {
            case SOUND -> new SoundBooster(soundFactory.get());
            case DRUNK -> new DrunkDriver();
            case HYBRID -> new HybridSystem();
        };
    }
}