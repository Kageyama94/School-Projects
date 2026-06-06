package model.observer;

public interface ObservableModel {
    void addListener(ModelListener l);
    void removeListener(ModelListener l);
    void notifyListeners(ModelEvent e);
}



