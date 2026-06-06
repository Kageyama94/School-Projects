package app;

import java.util.ArrayList;
import java.util.List;

import javax.swing.*;

import controller.GameController;
import model.GameConfig;
import model.GameConfig.Players.PlayerConfig;
import model.car.*;
import model.car.policy.*;
import model.game.*;
import model.track.*;
import view.*;

public class Main {
    private static final SoundPlayer.Factory VROOM_FACTORY =
        new SoundPlayer.Factory("/F1/assets/vrooom.wav");

    public static void main(String[] args) { SwingUtilities.invokeLater(Main::launch); }

    private static void launch() {
        List<PlayerConfig> configs = GameConfig.Players.ALL;
        String[] names = configs.stream().map(PlayerConfig::name).toArray(String[]::new);
        Track track = TrackFactory.circuit();

        SetupPanel decoPanel = new SetupPanel(names);
        askDecorations(decoPanel, names.length);

        List<Player> players = new ArrayList<>();
        for (int i = 0; i < names.length; i++) players.add(buildPlayer(i, configs.get(i), track, decoPanel));

        int tickMillis = GameConfig.Game.TICK_MILLIS;
        Game game = new Game(track, players, onTick -> new SwingTicker(tickMillis, onTick));
        GameController controller = new GameController(game);

        new Frame(game, controller);
        controller.pause();
    }

    private static void askDecorations(SetupPanel decoPanel, int carCount) {
        while (true) {
            JOptionPane pane = new JOptionPane(
                decoPanel,
                JOptionPane.PLAIN_MESSAGE,
                JOptionPane.DEFAULT_OPTION
            );
            JDialog dialog = pane.createDialog(null, "Décorations");
            dialog.setVisible(true);
            dialog.dispose();

            if (pane.getValue() == null || pane.getValue() == JOptionPane.UNINITIALIZED_VALUE) System.exit(0);
            if (decoPanel.allCarsDecorated()) return;

            JOptionPane.showMessageDialog(
                null,
                "Chaque voiture doit avoir au moins une décoration.",
                ViewConfig.TITLE_ERROR,
                JOptionPane.WARNING_MESSAGE
            );
        }
    }

    private static Player buildPlayer(int i, PlayerConfig cfg, Track track, SetupPanel decoPanel) {
        CarOption deco = decoPanel.getSelectedOptions(i).orElse(null);
        Policy policy = Policy.build(deco, VROOM_FACTORY::create);
        return new Player(cfg.name(), new Car(cfg.color(), track, policy));
    }
}