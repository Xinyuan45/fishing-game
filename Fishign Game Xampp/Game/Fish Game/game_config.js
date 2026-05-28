/**
 * Game Configuration
 * 
 * Centralized settings for all 4 fishing minigames.
 * Adjusting these values changes the difficulty and feel of the game directly.
 * 
 * Games:
 * 1. Classic Bar: Time the press when bar is in green zone.
 * 2. Reel In: Keep the fish inside the bar (Stardew Valley style).
 * 3. Sequence: Press the correct arrow keys in order.
 * 4. Balance: Keep the ball in the center using Left/Right keys.
 */
const GameConfig = {
    // Global Debug Flag
    debugMode: false,

    // Game 1: Classic Bar (Timing)
    game1: {
        baseSpeed: 2.0,           // Initial speed of the bar
        speedMultiplier: 1.0,     // How much speed increases per difficulty level
        baseTargetWidth: 25,      // Initial width of the green zone (%)
        widthMultiplier: 5,       // How much width decreases per difficulty level
        minTargetWidth: 5,        // Minimum width of the green zone (%)
        tolerance: 2              // Hit tolerance (%)
    },

    // Game 2: Reel In (Stardew Style)
    game2: {
        reelGravity: 0.4,         // How fast the bar falls
        reelLift: 0.8,            // How fast the bar rises when holding space
        barHeight: 30,            // Height of the catch bar (%)
        baseFishSpeed: 0.5,       // Initial speed of the fish
        fishSpeedMultiplier: 0.2, // Speed increase per difficulty

        // Progress Rates
        baseCatchRate: 0.8,       // How fast bar fills when catching (Base value)
        catchRateMultiplier: 0.1, // How much SLOWER it fills per difficulty (Subtraction)
        minCatchRate: 0.3,        // Minimum fill rate

        baseDrainRate: 0.1,       // How fast bar drains when missing (Base value)
        drainRateMultiplier: 0.05,// How much FASTER it drains per difficulty (Addition)

        startProgress: 25,        // Starting progress (%)
        winThreshold: 100,
        loseThreshold: 0
    },

    // Game 3: Sequence (Memory/Typing)
    game3: {
        baseTime: 300,            // Base time in frames (15s) - Increased to handle high difficulty
        timePenalty: 20,          // Frames removed per difficulty level
        baseLength: 4,            // Base sequence length
        lengthMultiplier: 2,      // Extra arrows per difficulty level

        // Visual Settings
        iconSize: "40px",         // Size of the arrows
        spacing: "15px",          // Space between arrows
        containerHeight: "100px"  // Height of the game container (to fit bigger icons)
    },

    // Game 4: Balance (Keep in Center)
    game4: {
        gravity: 0.2,             // Base gravity strength
        windChance: 0.05,         // Chance of wind changing gravity per frame
        windStrength: 0.1,        // Strength of wind change
        inputStrength: 0.5,       // How much arrow keys move the ball
        friction: 0.95,           // Velocity decay
        duration: 300,            // Game duration in frames (5s)
        zoneWidth: 30,            // Width of the center green zone (%)
        zoneLeft: 35              // Left position of the green zone (%)
    }
};
