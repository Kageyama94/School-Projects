package view;

import java.util.EnumSet;

import javax.swing.JPanel;

import model.observer.ModelEvent;
import model.observer.ModelListener;

public abstract class ObservingPanel extends JPanel implements ModelListener {
    private final EnumSet<ModelEvent.Type> triggers;

    protected ObservingPanel(ModelEvent.Type first, ModelEvent.Type... rest) {
        this.triggers = EnumSet.of(first, rest);
    }

    @Override
    public final void onModelEvent(ModelEvent e) {
        if (triggers.contains(e.type())) refresh();
    }

    protected abstract void refresh();
}
