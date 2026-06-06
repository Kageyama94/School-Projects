package view;

import java.awt.*;
import java.util.List;
import javax.swing.*;

import model.game.*;
import model.observer.*;

public class LeaderboardPanel extends JPanel implements ModelListener {
    private final Game game;
    private final JLabel tickLabel = titleLabel("", Font.ITALIC);
    private final JPanel rankingPanel = new JPanel();

    public LeaderboardPanel(Game game) {
        this.game = game;

        setLayout(new BoxLayout(this, BoxLayout.Y_AXIS));
        setBackground(Color.BLACK);

        rankingPanel.setLayout(new BoxLayout(rankingPanel, BoxLayout.Y_AXIS));
        rankingPanel.setOpaque(false);
        rankingPanel.setAlignmentX(Component.CENTER_ALIGNMENT);

        add(tickLabel);
        add(titleLabel("Leaderboard", Font.BOLD));
        add(rankingPanel);

        game.addListener(this);
        refresh();
    }

    private static JLabel titleLabel(String text, int style) {
        JLabel l = new JLabel(text);
        l.setForeground(ViewConfig.TEXT);
        l.setFont(l.getFont().deriveFont(style, ViewConfig.FONT_TITLE));
        l.setAlignmentX(Component.CENTER_ALIGNMENT);
        return l;
    }

    private void refresh() {
        tickLabel.setText("Tick : " + game.getTicks() + " ms");

        List<Player> ranking = game.getRanking();
        rankingPanel.removeAll();

        int rank = 1;
        for (Player p : ranking) {
            JLabel line = new JLabel(rank + ". " + p.getName());
            line.setFont(line.getFont().deriveFont(ViewConfig.FONT_LABEL));
            line.setForeground(medalColor(rank));
            line.setAlignmentX(Component.LEFT_ALIGNMENT);
            rankingPanel.add(line);
            rank++;
        }

        rankingPanel.revalidate();
        rankingPanel.repaint();
    }

    private Color medalColor(int rank) {
        return switch (rank) {
            case 1 -> ViewConfig.GOLD;
            case 2 -> ViewConfig.SILVER;
            case 3 -> ViewConfig.BRONZE;
            default -> ViewConfig.TEXT;
        };
    }

    @Override
    public void onModelEvent(ModelEvent e) {
        switch (e.type()) {
            case TICK, LAP_CHANGED, POSITION_CHANGED -> refresh();
            default -> {}
        }
    }
}