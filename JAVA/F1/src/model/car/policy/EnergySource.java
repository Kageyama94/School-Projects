package model.car.policy;

public interface EnergySource {
    int getLevel();
    void setLevel(int level);
    void recharge();
    default boolean isActive() { return getLevel() > 0; }
}
