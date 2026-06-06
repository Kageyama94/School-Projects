package model;

import java.util.List;

import model.characters.tasks.TaskType;

public record GameState(
        int totalCharacters,
        int visibleCharacters,
        int charactersAtExit,
        int deadCharacters,
        boolean gameEnded,
        boolean win,
        List<CharacterSnapshot> snapshots) {

    public record CharacterSnapshot(int x, int y, TaskType activeTask) {}
}
