package view;

import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.net.URL;
import javax.sound.sampled.*;

import model.car.policy.Sound;

public class SoundPlayer implements Sound {
    private final Clip clip;

    private SoundPlayer(Clip clip) { this.clip = clip; }

    public static class Factory {
        private final byte[] data;
        private final AudioFormat format;

        public Factory(String resourcePath) {
            byte[] tmpData = null;
            AudioFormat tmpFormat = null;
            try {
                URL url = SoundPlayer.class.getResource(resourcePath);
                if (url == null) {
                    System.err.println("Sample introuvable: " + resourcePath);
                } else {
                    try (AudioInputStream in = AudioSystem.getAudioInputStream(url)) {
                        tmpFormat = in.getFormat();
                        tmpData = in.readAllBytes();
                    }
                }
            } catch (UnsupportedAudioFileException | IOException e) {
                System.err.println("Erreur chargement son: " + e.getMessage());
            }
            this.data = tmpData;
            this.format = tmpFormat;
        }

        public SoundPlayer create() {
            if (data == null) return new SoundPlayer(null);
            try (InputStream bytes = new ByteArrayInputStream(data);
                AudioInputStream ais = new AudioInputStream(bytes, format, data.length / format.getFrameSize())) {
                Clip c = AudioSystem.getClip();
                c.open(ais);
                return new SoundPlayer(c);
            } catch (IOException | LineUnavailableException e) {
                System.err.println("Erreur création clip: " + e.getMessage());
                return new SoundPlayer(null);
            }
        }
    }

    @Override
    public void play() {
        if (clip == null) return;
        clip.stop();
        clip.setFramePosition(0);
        clip.start();
    }
}