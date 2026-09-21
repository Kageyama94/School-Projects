package model;

import model.characters.tasks.TaskType;

public class SelectionModel {
    private TaskType selectedTask = null;

    public void select(TaskType task) { this.selectedTask = task; }
    public TaskType getSelected() { return selectedTask; }
}