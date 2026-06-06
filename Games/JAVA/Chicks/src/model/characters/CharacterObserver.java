package model.characters;

public interface CharacterObserver {
    void onCharacterEnd(boolean hasReachedExit, boolean isDead);
}
