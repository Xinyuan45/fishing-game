<!DOCTYPE html>
<html>
<head>
    <title>Deep Ocean Fishing - Theme Song</title>
    <style>
        body {
            background: linear-gradient(135deg, #1a2332 0%, #2d3748 50%, #1a2f3f 100%);
            color: white;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .music-player {
            background: rgba(42, 67, 101, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(160, 174, 192, 0.15);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            max-width: 500px;
        }
        .lyrics {
            font-size: 18px;
            line-height: 1.8;
            margin: 20px 0;
            animation: fadeIn 1s ease-in;
        }
        .verse {
            margin: 20px 0;
            opacity: 0;
            animation: slideIn 0.5s ease-out forwards;
        }
        .verse:nth-child(1) { animation-delay: 0s; }
        .verse:nth-child(2) { animation-delay: 2s; }
        .verse:nth-child(3) { animation-delay: 4s; }
        .verse:nth-child(4) { animation-delay: 6s; }
        .chorus {
            font-weight: bold;
            color: #4fd1c5;
            font-size: 20px;
            text-shadow: 0 0 10px rgba(79, 209, 197, 0.5);
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        .wave {
            display: inline-block;
            animation: wave 2s ease-in-out infinite;
        }
        @keyframes wave {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <div class="music-player">
        <h1>🎵 Deep Ocean Fishing 🎣</h1>
        <h3>Theme Song</h3>
        
        <div class="lyrics">
            <div class="verse">
                <span class="wave">🌊</span> Cast your line into the deep blue sea <span class="wave">🌊</span><br>
                Where the ocean's treasures wait for thee
            </div>
            
            <div class="verse chorus">
                Deep Ocean Fishing, where legends are born<br>
                From the break of dawn to the early morn<br>
                Catch the rarest fish, feel the ocean's call<br>
                In the Deep Ocean, we catch 'em all!
            </div>
            
            <div class="verse">
                <span class="wave">🐟</span> From Sunny Coast to the Abyssal Void <span class="wave">🐟</span><br>
                Every fish you catch brings you joy
            </div>
            
            <div class="verse">
                <span class="wave">🏆</span> Level up your skills, unlock the maps <span class="wave">🏆</span><br>
                Daily rewards and achievement caps<br>
                With your fishing rod and a bit of luck<br>
                That Legendary fish won't stay stuck!
            </div>
            
            <div class="verse chorus">
                Deep Ocean Fishing, where legends are born<br>
                From the break of dawn to the early morn<br>
                Catch the rarest fish, feel the ocean's call<br>
                In the Deep Ocean, we catch 'em all!
            </div>
        </div>
        
        <p style="margin-top: 30px; color: #a0aec0;">
            <em>🎼 Composed for the Deep Ocean Fishing Game 🎼</em>
        </p>
        
        <a href="dashboard.php" style="display: inline-block; margin-top: 20px; padding: 10px 30px; background: #4fd1c5; color: white; text-decoration: none; border-radius: 10px; font-weight: bold;">
            Back to Game
        </a>
    </div>
</body>
</html>
