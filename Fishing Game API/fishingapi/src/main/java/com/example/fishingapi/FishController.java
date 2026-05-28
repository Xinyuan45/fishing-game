package com.example.fishingapi;

import org.springframework.web.bind.annotation.*;
import java.util.*;

@RestController
@RequestMapping("/api")
public class FishController {

    private final Random random = new Random();

    // Fish pools by location and rarity
    private static final Map<String, Map<String, String[]>> LOCATION_FISH = new HashMap<>() {
        {
            put("Sunny Coast", new HashMap<>() {
                {
                    put("Common", new String[] { "Carp", "Goldfish", "Sardine", "Anchovy", "Mackerel" });
                    put("Rare", new String[] { "Sea Bass", "Red Snapper", "Flounder", "Mullet" });
                    put("Epic", new String[] { "Dolphin", "Manta Ray", "Sailfish" });
                    put("Legendary", new String[] { "Golden Marlin", "Sea Dragon" });
                }
            });

            put("Coral Reef", new HashMap<>() {
                {
                    put("Common", new String[] { "Clownfish", "Tang", "Parrotfish", "Angelfish", "Butterflyfish" });
                    put("Rare", new String[] { "Lionfish", "Moray Eel", "Octopus", "Pufferfish" });
                    put("Epic", new String[] { "Giant Clam", "Sea Turtle", "Reef Shark", "Barracuda" });
                    put("Legendary", new String[] { "Rainbow Serpent", "Coral Guardian" });
                }
            });

            put("Deep Trench", new HashMap<>() {
                {
                    put("Common", new String[] { "Lanternfish", "Hatchetfish", "Viperfish", "Dragonfish" });
                    put("Rare", new String[] { "Gulper Eel", "Fangtooth", "Anglerfish", "Giant Isopod" });
                    put("Epic", new String[] { "Giant Squid", "Colossal Squid", "Oarfish", "Frilled Shark" });
                    put("Legendary", new String[] { "Kraken", "Leviathan", "Abyssal Wyrm" });
                }
            });

            put("Abyssal Void", new HashMap<>() {
                {
                    put("Common", new String[] { "Ghostfish", "Void Shrimp", "Shadow Eel", "Phantom Ray" });
                    put("Rare", new String[] { "Void Stalker", "Abyss Crawler", "Dark Manta", "Spectral Squid" });
                    put("Epic", new String[] { "Elder Kraken", "Void Leviathan", "Nightmare Whale", "Abyssal Dragon" });
                    put("Legendary", new String[] { "Megalodon", "Cthulhu", "Void Emperor", "Ancient One" });
                }
            });
        }
    };

    @GetMapping("/fish")
    public Map<String, String> getFish(
            @RequestParam(defaultValue = "1.0") double luck,
            @RequestParam(defaultValue = "Sunny Coast") String location) {

        double roll = random.nextDouble();
        double adjustedRoll = 1 - (1 - roll) / luck;

        String rarity;
        if (adjustedRoll < 0.60)
            rarity = "Common";
        else if (adjustedRoll < 0.85)
            rarity = "Rare";
        else if (adjustedRoll < 0.97)
            rarity = "Epic";
        else
            rarity = "Legendary";

        // Get fish pool for location, fallback to Sunny Coast if location not found
        Map<String, String[]> locationPool = LOCATION_FISH.getOrDefault(location, LOCATION_FISH.get("Sunny Coast"));
        String[] fishList = locationPool.getOrDefault(rarity, locationPool.get("Common"));

        String fish = fishList[random.nextInt(fishList.length)];

        // Calculate difficulty based on rarity
        int difficulty = switch (rarity) {
            case "Rare" -> 2;
            case "Epic" -> 3;
            case "Legendary" -> 4;
            default -> 1;
        };

        return Map.of(
                "fish", fish,
                "rarity", rarity,
                "difficulty", String.valueOf(difficulty),
                "roll", String.format("%.4f", adjustedRoll));
    }

    @GetMapping("/fish/all")
    public Map<String, Map<String, String[]>> getAllFish() {
        return LOCATION_FISH;
    }
}
