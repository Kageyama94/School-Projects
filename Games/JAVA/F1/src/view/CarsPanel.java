package view;

import java.awt.*;
import javax.swing.*;

import controller.GameController;
import controller.GameController.ChangeSpeedError;
import model.car.*;
import model.game.*;
import model.observer.*;

public class CarsPanel extends JPanel implements ModelListener {
    private final Player player;
    private final GameController controller;
    private final JLabel nameLabel = buildLabel();
    private final JLabel energyLabel = buildLabel();
    private final JLabel lapsLabel = buildLabel();
    private final JLabel stateLabel = buildLabel();

    public CarsPanel(Player player, GameController controller) {
        this.player = player;
        this.controller = controller;
        setLayout(new BoxLayout(this, BoxLayout.Y_AXIS));
        setBackground(player.getCar().getColor());

        add(nameLabel);
        add(energyLabel);
        add(lapsLabel);
        add(stateLabel);

        addButtons();
        controller.getGame().addListener(this);
        refresh();
    }

    private static JLabel buildLabel() {
        JLabel l = new JLabel();
        l.setForeground(ViewConfig.TEXT);
        l.setAlignmentX(Component.CENTER_ALIGNMENT);
        l.setFont(l.getFont().deriveFont(Font.BOLD, ViewConfig.FONT_TITLE));
        return l;
    }

    private void addButtons() {
        JButton bwd = new JButton("Ralentir");
        JButton fwd = new JButton("Accélérer");

        JPanel btns = new JPanel(new FlowLayout(FlowLayout.CENTER, ViewConfig.BUTTON_GAP, 0));
        btns.setOpaque(false);
        btns.add(bwd);
        btns.add(fwd);
        add(btns);

        bwd.addActionListener(_ -> changeSpeed(-1));
        fwd.addActionListener(_ -> changeSpeed(+1));
    }

    private void changeSpeed(int delta) {
        ChangeSpeedError err = controller.changeSpeed(player, delta);
        if (err != null) showError(ViewConfig.message(err));
    }

    public void showError(String message) {
        Window parent = SwingUtilities.getWindowAncestor(this);
        JOptionPane pane = new JOptionPane(
            message, JOptionPane.WARNING_MESSAGE, JOptionPane.DEFAULT_OPTION
        );
        JDialog dialog = pane.createDialog(parent, ViewConfig.TITLE_ERROR);
        dialog.setModal(false);
        dialog.setAlwaysOnTop(true);
        dialog.setVisible(true);

        Timer t = new Timer(ViewConfig.ERROR_DIALOG_MS, _ -> dialog.dispose());
        t.setRepeats(false);
        t.start();
    }

    private void refresh() {
        Car car = player.getCar();

        nameLabel.setText("Voiture " + player.getName());
        energyLabel.setText(car.isOnBattery()
            ? "Batterie: " + car.getBatteryLevel() + "%"
            : "Carburant: " + car.getFuel());
        lapsLabel.setText("Tour de piste: " + car.getLaps());

        String state = "État: " + car.getState();
        if (car.getState() == Car.State.DAMAGED) state += "(" + car.getDamageTurnsLeft() + ")";
        stateLabel.setText(state);
    }

    @Override public void onModelEvent(ModelEvent e) {
        switch (e.type()) {
            case TICK, STATE_CHANGED, FUEL_CHANGED, LAP_CHANGED -> refresh();
            default -> {}
        }
    }
}