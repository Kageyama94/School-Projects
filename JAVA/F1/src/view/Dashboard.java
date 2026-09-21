package view;

import javax.swing.*;

import controller.GameController;
import model.game.Game;

import java.awt.*;

public class Dashboard extends JPanel {
    public Dashboard(Game game, GameController controller) {
        setLayout(new GridLayout(1, 0));

        for (var p : game.getPlayers()) add(new CarsPanel(p, controller));
        add(new LeaderboardPanel(game));
    }
}
