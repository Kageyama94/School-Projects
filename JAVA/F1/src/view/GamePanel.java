package view;

import java.awt.*;
import java.awt.image.BufferedImage;
import java.util.List;
import java.util.*;

import model.car.Car;
import model.game.*;
import model.observer.*;
import model.track.*;
import model.track.Track.*;

public class GamePanel extends ObservingPanel {
    private final Game game;
    private int cellW, cellH;
    private BufferedImage boardCache;

    public GamePanel(Game game) {
        super(ModelEvent.Type.TICK, ModelEvent.Type.POSITION_CHANGED, ModelEvent.Type.STATE_CHANGED);
        this.game = game;
        setPreferredSize(ViewConfig.GAME_SIZE);
        setBackground(ViewConfig.DARK);
        game.addListener(this);
    }

    @Override
    protected void paintComponent(Graphics g) {
        super.paintComponent(g);
        Track track = game.getTrack();

        cellW = Math.max(1, getWidth() / track.cols());
        cellH = Math.max(1, getHeight() / track.rows());

        if (boardCache == null || boardCache.getWidth() != getWidth() || boardCache.getHeight() != getHeight())
            boardCache = renderBoard(track);
        g.drawImage(boardCache, 0, 0, null);
        drawCars(track, g);
    }

    private BufferedImage renderBoard(Track track) {
        int w = Math.max(getWidth(), 1), h = Math.max(getHeight(), 1);
        BufferedImage img = new BufferedImage(w, h, BufferedImage.TYPE_INT_ARGB);
        Graphics2D bg = img.createGraphics();
        bg.setColor(getBackground());
        bg.fillRect(0, 0, w, h);
        Font numberFont = bg.getFont().deriveFont(Font.BOLD, Math.min(cellW, cellH) * ViewConfig.CELL_FONT_RATIO);
        drawBoard(track, bg, numberFont);
        bg.dispose();
        return img;
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
        Map<Integer, List<Car>> carsByIndex = new HashMap<>();
        for (var p : game.getPlayers()) {
            Car car = p.getCar();
            carsByIndex.computeIfAbsent(car.getRoadIndex(), i -> new ArrayList<>()).add(car);
        }

        List<Cell> road = track.getRoad();
        for (var e : carsByIndex.entrySet()) {
            int idx = e.getKey();
            Cell pos = road.get(idx);
            Cell next = road.get((idx + 1) % road.size());

            boolean vertical = (next.col() == pos.col());
            drawCarsInCell(g, e.getValue(), pos.col() * cellW, pos.row() * cellH, vertical);
        }
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
    protected void refresh() { repaint(); }
}