package view;

import java.awt.*;
import java.util.*;
import java.util.List;
import java.util.stream.IntStream;

import javax.swing.*;

import model.car.policy.CarOption;

public class SetupPanel extends JPanel {
    private final List<Map<CarOption, JRadioButton>> buttons = new ArrayList<>();

    public SetupPanel(String[] carNames) {
        setLayout(new BoxLayout(this, BoxLayout.Y_AXIS));

        JLabel title = new JLabel("Choix des décorations");
        title.setAlignmentX(Component.CENTER_ALIGNMENT);
        title.setFont(title.getFont().deriveFont(Font.BOLD, ViewConfig.FONT_HEADER));
        add(title);

        for (int i = 0; i < carNames.length; i++) {
            buttons.add(new EnumMap<>(CarOption.class));
            add(rowForCar(i, carNames[i]));
            add(Box.createVerticalStrut(ViewConfig.SETUP_ROW_STRUT));
        }
    }

    private JPanel rowForCar(int index, String name) {
        JPanel row = new JPanel(new FlowLayout());

        JLabel label = new JLabel(name);
        label.setPreferredSize(ViewConfig.SETUP_LABEL_SIZE);
        row.add(label);

        ButtonGroup group = new ButtonGroup();
        for (CarOption d : CarOption.values()) {
            JRadioButton btn = new JRadioButton(d.getLabel());
            buttons.get(index).put(d, btn);
            group.add(btn);
            row.add(btn);
        }
        return row;
    }

    public Optional<CarOption> getSelectedOptions(int carIndex) {
        Map<CarOption, JRadioButton> row = buttons.get(carIndex);
        for (CarOption d : CarOption.values()) {
            if (row.get(d).isSelected()) return Optional.of(d);
        }
        return Optional.empty();
    }

    public boolean allCarsDecorated() {
        return IntStream.range(0, buttons.size())
            .allMatch(i -> getSelectedOptions(i).isPresent());
    }
}
