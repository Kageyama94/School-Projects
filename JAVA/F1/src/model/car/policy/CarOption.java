package model.car.policy;

public enum CarOption {
    SOUND("Sound Booster"),
    DRUNK("Drunk Driver"),
    HYBRID("Hybrid System");

    private final String label;
    
    CarOption(String label) { this.label = label; }
    
    public String getLabel() { return label; }
}
