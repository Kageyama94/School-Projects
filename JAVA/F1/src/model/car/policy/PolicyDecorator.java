package model.car.policy;

public abstract class PolicyDecorator implements Policy {
    protected final StandardDriving movement = new StandardDriving();
}
