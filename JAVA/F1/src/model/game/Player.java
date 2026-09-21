package model.game;

import model.car.Car;

public class Player {
    private final String name;
    private final Car car;

    public Player(String name, Car car) {
        this.name = name;
        this.car = car;
    }
    
    public String getName() { return name; }
    public Car getCar() { return car; }
}
