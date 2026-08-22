package view;

import java.awt.*;
import javax.swing.*;

import controller.GameController;
import controller.GameController.ChangeSpeedError;
import model.car.*;
import model.game.*;
import model.observer.*;

public class CarsPanel extends ObservingPanel {
    private final Player player;
    private final GameController controller;
    private final JLabel nameLabel = titleLabel();
    private final JLabel energyLabel = titleLabel();
    private final JLabel lapsLabel = titleLabel();
    private final JLabel stateLabel = titleLabel();
    private final JButton bwdButton = new JButton("Ralentir");
    private final JButton fwdButton = new JButton("Accélérer");

    public CarsPanel(Player player, GameController controller) {
        super(ModelEvent.Type.TICK, ModelEvent.Type.STATE_CHANGED, ModelEvent.Type.FUEL_CHANGED, ModelEvent.Type.LAP_CHANGED);
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

    private static JLabel titleLabel() { return ViewConfig.label("", Font.BOLD, ViewConfig.FONT_TITLE); }

    private void addButtons() {
        JPanel btns = new JPanel(new FlowLayout(FlowLayout.CENTER, ViewConfig.BUTTON_GAP, 0));
        btns.setOpaque(false);
        btns.add(bwdButton);
        btns.add(fwdButton);
        add(btns);

        bwdButton.addActionListener(_ -> changeSpeed(-1));
        fwdButton.addActionListener(_ -> changeSpeed(+1));
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

    @Override
    protected void refresh() {
        Car car = player.getCar();

        boolean running = controller.getGame().getState() == Game.State.RUNNING;
        bwdButton.setEnabled(running);
        fwdButton.setEnabled(running);

        nameLabel.setText("Voiture " + player.getName());
        energyLabel.setText(car.isOnBattery()
            ? "Batterie: " + car.getBatteryLevel() + "%"
            : "Carburant: " + car.getFuel());
        lapsLabel.setText("Tour de piste: " + car.getLaps());

        String state = "État: " + car.getState();
        if (car.getState() == Car.State.DAMAGED) state += "(" + car.getDamageTurnsLeft() + ")";
        stateLabel.setText(state);
    }
}