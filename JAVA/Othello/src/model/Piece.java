package model;

public enum Piece {
    EMPTY, BLACK, WHITE;

    public Piece opposite() {
        if (this == BLACK) return WHITE;
        if (this == WHITE) return BLACK;
        return EMPTY;
    }
}