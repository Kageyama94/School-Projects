package model;

import java.awt.Color;
import java.util.List;

public final class GameConfig {
    private GameConfig() {}

    public static final class Car {
        public static final int INITIAL_FUEL = 60;
        public static final int DAMAGE_DURATION = 5;
        private Car() {}
    }

    public static final class Fuel {
        public static final int CONSUMPTION_LOW = 1;
        public static final int CONSUMPTION_NORMAL = 2;
        public static final int CONSUMPTION_BOOST = 5;
        private Fuel() {}
    }

    public static final class Battery {
        public static final int MAX = 100;
        public static final int CONSUMPTION = 10;
        public static final int RECHARGE = 5;
        private Battery() {}
    }

    public static final class Movement {
        public static final int LOW_MIN = 1, LOW_MAX = 3;
        public static final int NORMAL_MIN = 1, NORMAL_MAX = 6;
        public static final int BOOST_MIN = 5, BOOST_MAX = 10;
        private Movement() {}
    }

    public static final class Game {
        public static final int LAPS_TO_WIN = 3;
        public static final int TICK_MILLIS = 100;
        private Game() {}
    }

    public static final class Track {
        public static final int ROWS = 10;
        public static final int COLS = 20;
        private Track() {}
    }

    public static final class Players {
        public record PlayerConfig(String name, Color color) {}

        public static final List<PlayerConfig> ALL = List.of(
            new PlayerConfig("Rouge", Color.RED),
            new PlayerConfig("Orange", Color.ORANGE),
            new PlayerConfig("Bleu", Color.BLUE)
        );
        private Players() {}
    }
}