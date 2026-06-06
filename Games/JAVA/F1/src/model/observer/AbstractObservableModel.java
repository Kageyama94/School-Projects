package model.observer;

import java.util.ArrayList;
import java.util.List;

public abstract class AbstractObservableModel implements ObservableModel {
    private final List<ModelListener> listeners = new ArrayList<>();

    @Override public void addListener(ModelListener l) { listeners.add(l); }
    @Override public void removeListener(ModelListener l) { listeners.remove(l); }
    @Override public void notifyListeners(ModelEvent e) {
        for (var l : List.copyOf(listeners)) l.onModelEvent(e);
    }

    protected void fire(ModelEvent.Type type) { notifyListeners(new ModelEvent(type)); }
}