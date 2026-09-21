package model.observer;

public record ModelEvent(Type type) {
    public enum Type { TICK, STATE_CHANGED, FUEL_CHANGED, POSITION_CHANGED, LAP_CHANGED, FINISHED }
}
