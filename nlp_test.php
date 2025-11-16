<?php

class SimpleChatbot {
    private $intents;
    private $responses;
    
    public function __construct($intentsFile, $responsesFile) {
        // Load intents
        $intentsJson = file_get_contents($intentsFile);
        $this->intents = json_decode($intentsJson, true);
        
        // Load responses
        $responsesJson = file_get_contents($responsesFile);
        $this->responses = json_decode($responsesJson, true);
    }
    
    /**
     * Preprocessing teks yang lebih ringan - HANYA lowercase dan trim
     * Jangan hapus tanda baca agar "what's" tetap "what's" bukan "whats"
     */
    private function preprocessText($text) {
        $text = mb_strtolower(trim($text), 'UTF-8');
        // Hapus hanya karakter khusus yang tidak perlu, pertahankan apostrof dan tanda hubung
        $text = preg_replace('/[^\w\s\-\']/u', '', $text);
        // Normalisasi spasi
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }
    
    /**
     * Hitung similarity score dengan prioritas exact match
     */
    private function calculateSimilarity($userInput, $pattern) {
        $userInputClean = $this->preprocessText($userInput);
        $patternClean = $this->preprocessText($pattern);
        
        // 1. EXACT MATCH - Prioritas tertinggi
        if ($userInputClean === $patternClean) {
            return 1.0;
        }
        
        // 2. CONTAINS MATCH - Prioritas tinggi
        if (strpos($userInputClean, $patternClean) !== false) {
            return 0.95;
        }
        if (strpos($patternClean, $userInputClean) !== false) {
            return 0.90;
        }
        
        // 3. WORD-BASED MATCHING - Lebih akurat untuk kata tunggal
        $userWords = explode(' ', $userInputClean);
        $patternWords = explode(' ', $patternClean);
        
        // Untuk input 1 kata, berikan bobot lebih tinggi
        if (count($userWords) === 1 && count($patternWords) === 1) {
            similar_text($userInputClean, $patternClean, $percent);
            return $percent / 100;
        }
        
        // Hitung word overlap
        $matchingWords = count(array_intersect($userWords, $patternWords));
        $totalUniqueWords = count(array_unique(array_merge($userWords, $patternWords)));
        
        $wordSim = $totalUniqueWords > 0 ? ($matchingWords / $totalUniqueWords) : 0;
        
        // 4. Similar text sebagai fallback
        similar_text($userInputClean, $patternClean, $textSimPercent);
        $textSim = $textSimPercent / 100;
        
        // 5. Gabungkan scores dengan bobot yang lebih baik
        $finalScore = ($wordSim * 0.6) + ($textSim * 0.4);
        
        return $finalScore;
    }
    
    /**
     * Deteksi intent dengan threshold yang lebih realistis
     */
    private function detectIntent($message) {
        $bestMatch = null;
        $highestScore = 0;
        
        // THRESHOLD YANG LEBIH RENDAH untuk handling lebih banyak intents
        $threshold = 0.45; // Diturunkan dari 0.65
        
        // DEBUG: Uncomment baris berikut untuk melihat proses
        // echo "DEBUG: Processing: '$message'<br>";
        
        foreach ($this->intents as $intent) {
            $intentBestScore = 0;
            
            foreach ($intent['patterns'] as $pattern) {
                $score = $this->calculateSimilarity($message, $pattern);
                
                // DEBUG: Uncomment untuk melihat similarity scores
                // if ($score > 0.3) {
                //     echo "DEBUG: '$message' vs '$pattern' = " . number_format($score, 2) . "<br>";
                // }
                
                if ($score > $intentBestScore) {
                    $intentBestScore = $score;
                }
                
                // Jika exact match, langsung return (optimization)
                if ($score === 1.0) {
                    // echo "DEBUG: EXACT MATCH found for '$message'<br>";
                    return $intent['intent'];
                }
            }
            
            if ($intentBestScore > $highestScore) {
                $highestScore = $intentBestScore;
                $bestMatch = $intent['intent'];
            }
        }
        
        // DEBUG: Uncomment untuk melihat final decision
        // echo "DEBUG: Best match: $bestMatch (Score: " . number_format($highestScore, 2) . ")<br>";
        
        return ($highestScore >= $threshold) ? $bestMatch : 'unknown';
    }
    
    /**
     * Ambil response berdasarkan intent
     */
    private function getResponse($intent) {
        if (isset($this->responses[$intent])) {
            $responses = $this->responses[$intent];
            return $responses[array_rand($responses)];
        }
        
        // Fallback untuk 'unknown'
        if (isset($this->responses['unknown'])) {
            return $this->responses['unknown'][array_rand($this->responses['unknown'])];
        }
        
        return "I'm not sure how to respond to that.";
    }
    
    /**
     * Main function dengan debugging option
     */
    public function getBotResponse($message, $debug = false) {
        if (empty(trim($message))) {
            return "Please type a message.";
        }
        
        $intent = $this->detectIntent($message);
        $response = $this->getResponse($intent);
        
        if ($debug) {
            return "Intent: $intent | Response: $response";
        }
        
        return $response;
    }
    
    /**
     * Function khusus untuk debugging similarity
     */
    public function debugSimilarity($message) {
        echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>";
        echo "<strong>Debug for: '$message'</strong><br>";
        
        foreach ($this->intents as $intent) {
            foreach ($intent['patterns'] as $pattern) {
                $score = $this->calculateSimilarity($message, $pattern);
                if ($score > 0.3) { // Hanya tampilkan yang relevan
                    echo "vs '$pattern' = " . number_format($score, 2) . " [{$intent['intent']}]<br>";
                }
            }
        }
        
        $intent = $this->detectIntent($message);
        echo "<strong>Final intent: $intent</strong>";
        echo "</div>";
        
        return $this->getResponse($intent);
    }
}

// =============================================
// TESTING & IMPLEMENTATION
// =============================================

// Inisialisasi chatbot
$chatbot = new SimpleChatbot('intents.json', 'responses.json');

/*
// Test dengan berbagai input
$testMessages = [
    // Exact matches yang seharusnya work
    "hello", "hi", "hey", 
    "thanks", "thank you",
    "products", "what products",
    "order", "status",
    
    // Partial matches
    "hello there", "hi friend",
    "thank you so much", 
    "show me products", "what do you sell",
    "my order status", "where is my order",
    
    // Single word tests
    "bye", "goodbye", "price", "help",
    
    // Should be unknown
    "weather", "random text", "asdfgh"
];

echo "<h2>Chatbot Test Results</h2>";
echo "<div style='font-family: Arial, sans-serif;'>";

foreach ($testMessages as $message) {
    $response = $chatbot->getBotResponse($message);
    echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 10px;'>";
    echo "<strong>User:</strong> $message<br>";
    echo "<strong>Bot:</strong> $response";
    echo "</div>";
}

echo "</div>";

// Debug specific problematic messages
echo "<h2>Debug Problematic Messages</h2>";
$problematic = ["hello", "hi", "thanks", "products", "order"];
foreach ($problematic as $msg) {
    $chatbot->debugSimilarity($msg);
}
*/  
?>

    <div class="chat-container">
        <h1>NLP Chatbot - Testing</h1>
        
        <form method="post">
            <input type="text" name="message" placeholder="Type your message..." required>
            <button type="submit">Send</button>
            <br>
            <label>
                <input type="checkbox" name="debug"> Debug mode
            </label>
        </form>
        
        <?php if (isset($_POST['message'])): ?>
            <?php
            $userMessage = $_POST['message'];
            $debugMode = isset($_POST['debug']);
            $botResponse = $chatbot->getBotResponse($userMessage, $debugMode);
            ?>
            <div class="message user">
                <strong>You:</strong> <?php echo htmlspecialchars($userMessage); ?>
            </div>
            <div class="message bot">
                <strong>Bot:</strong> <?php echo htmlspecialchars($botResponse); ?>
            </div>
            
            <?php if ($debugMode): ?>
                <div style="background: #fff3cd; padding: 10px; margin: 10px 0;">
                    <strong>Debug Info:</strong><br>
                    <?php $chatbot->debugSimilarity($userMessage); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>