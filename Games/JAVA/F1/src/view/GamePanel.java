package view;

import javax.swing.*;
import java.awt.*;
import java.util.List;
import java.util.ArrayList;
import java.util.*;

import model.car.Car;
import model.game.*;
import model.observer.*;
import model.track.*;
import model.track.Track.*;

public class GamePanel extends JPanel implements ModelListener {
    private final Game game;
    private int cellW, cellH;

    public GamePanel(Game game) {
        this.game = game;
        setPreferredSize(ViewConfig.GAME_SIZE);
        setBackground(ViewConfig.DARK);
        game.addListener(this);
    }

    @Override
    protected void paintComponent(Graphics g) {
        super.paintComponent(g);
        Track track = game.getTrack();

        cellW = getWidth() / track.cols();
        cellH = getHeight() / track.rows();
        Font numberFont = g.getFont().deriveFont(Font.BOLD, Math.min(cellW, cellH) * ViewConfig.CELL_FONT_RATIO);

        drawBoard(track, g, numberFont);
        drawCars(track, g);
    }

    private void drawBoard(Track track, Graphics g, Font numberFont) {
        for (int r = 0; r < track.rows(); r++) {
            for (int c = 0; c < track.cols(); c++) {
                drawCell(track, g, r, c, track.get(r, c), numberFont);
            }
        }
    }
 
    private void drawCell(Track track, Graphics g, int r, int c, Type t, Font numberFont) {
        int x = c * cellW, y = r * cellH;
 
        g.setColor(cellColor(t));
        g.fillRect(x, y, cellW - 1, cellH - 1);
 
        if (t == Type.TURN) {
            g.setColor(ViewConfig.MARKER);
            g.fillRect(x + ViewConfig.CELL_INSET,
                       y + ViewConfig.CELL_INSET,
                       cellW - 2 * ViewConfig.CELL_INSET - 1,
                       cellH - 2 * ViewConfig.CELL_INSET - 1);
        }
 
        String mark = cellMark(track, r, c, t);
        if (mark != null) {
            g.setColor(ViewConfig.TEXT_ON_MAP);
            g.setFont(numberFont);
            center(g, mark, x, y, cellW, cellH);
        }
    }

    private static Color cellColor(Type t) {
        return switch (t) {
            case GRASS -> ViewConfig.GRASS;
            case ROAD, TURN -> ViewConfig.ROAD;
            case START, FINISH -> ViewConfig.MARKER;
        };
    }

    private static String cellMark(Track track, int r, int c, Type t) {
        return switch (t) {
            case START -> "D";
            case FINISH -> "A";
            case TURN -> {
                Integer num = track.getTurnAt(r, c);
                yield num == null ? null : String.valueOf(num);
            }
            default -> null;
        };
    }

    private void drawCars(Track track, Graphics g) {
        Map<Cell, List<Car>> cellMap = carsByCell(track);
        List<Cell> road = track.getRoad();

        for (var e : cellMap.entrySet()) {
            Cell pos = e.getKey();
            int idx = road.indexOf(pos);
            if (idx == -1) continue;

            Cell next = (idx < road.size() - 1) ? road.get(idx + 1) : road.get(idx - 1);

            boolean vertical = (next.col() == pos.col());
            drawCarsInCell(g, e.getValue(), pos.col() * cellW, pos.row() * cellH, vertical);
        }
    }

    private Map<Cell, List<Car>> carsByCell(Track track) {
        Map<Cell, List<Car>> cellMap = new HashMap<>();
        List<Cell> road = track.getRoad();

        for (var p : game.getPlayers()) {
            Car car = p.getCar();
            Cell pos = road.get(car.getRoadIndex());
            cellMap.computeIfAbsent(pos, e -> new ArrayList<>()).add(car);
        }
        return cellMap;
    }

    private void drawCarsInCell(Graphics g, List<Car> cars, int cellX, int cellY, boolean vertical) {
        int n = cars.size();
        int w = cellW - 2 * ViewConfig.CELL_INSET - 1;
        int h = cellH - 2 * ViewConfig.CELL_INSET - 1;

        int carW = vertical ? w / n : w;
        int carH = vertical ? h : h / n;
        int dx = vertical ? carW : 0;
        int dy = vertical ? 0 : carH;

        for (int i = 0; i < n; i++) {
            Car car = cars.get(i);
            g.setColor(car.getColor());
            g.fillRect(cellX + ViewConfig.CELL_INSET + i * dx,
                       cellY + ViewConfig.CELL_INSET + i * dy,
                       carW, carH);
        }
    }

    private static void center(Graphics g, String text, int x, int y, int w, int h) {
        FontMetrics fm = g.getFontMetrics();
        int tx = x + (w - fm.stringWidth(text)) / 2;
        int ty = y + (h - fm.getHeight()) / 2 + fm.getAscent();
        g.drawString(text, tx, ty);
    }

    @Override
    public void onModelEvent(ModelEvent e) {
        switch (e.type()) {
            case TICK, POSITION_CHANGED, STATE_CHANGED -> repaint();
            default -> {}
        }
    }
}