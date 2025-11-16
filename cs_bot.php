<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Jakarta');

/** By sourceid */

include('config.php');

// Display errors for debugging, remove this line on production
ini_set('display_errors', 1);
error_reporting(E_ALL);

$unknownx = false;

// cleanup old files
if (isset($_POST['message']) && $_POST['message'] == "hi") { 
    foreach (glob($chat_dir . '/*.txt') as $f) { 
        if (filemtime($f) < strtotime('-150 days')) @unlink($f); 
    } 
}

// --- Simple NLP function ---

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
        global $threshold;
        
        foreach ($this->intents as $intent) {
            $intentBestScore = 0;
            
            foreach ($intent['patterns'] as $pattern) {
                $score = $this->calculateSimilarity($message, $pattern);
                
                if ($score > $intentBestScore) {
                    $intentBestScore = $score;
                }
                
                // Jika exact match, langsung return (optimization)
                if ($score === 1.0) {
                    return $intent['intent'];
                }
            }
            
            if ($intentBestScore > $highestScore) {
                $highestScore = $intentBestScore;
                $bestMatch = $intent['intent'];
            }
        }
        
        // DEBUG: Uncomment untuk melihat final decision
        if (!$bestMatch) { global $unknownx; $unknownx = true; }
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
}

// --- ELIZA function ---

class ElizaBot {
    private $chatDir;
    private $patternFile = 'pattern.json';
    private $specialPatternsFile = 'special.json';
    private $userFile;
    private $patterns;
    private $specialPatterns;

    public function __construct() {
        global $chat_dir;  
        $this->chatDir = $chat_dir;
        // 1. Setup paths and directories
        $ip_addr = str_replace('.', '_', $_SERVER['REMOTE_ADDR']);
        $this->userFile = $this->chatDir . $ip_addr . '.txt';

        // 2. Load data
        $this->patterns = $this->loadJson($this->patternFile);
        $this->specialPatterns = $this->loadJson($this->specialPatternsFile);
    }

    // --- File Utility Functions ---

    private function loadJson($file) {
        if (!file_exists($file)) return [];
        $content = file_get_contents($file);
        return json_decode($content, true) ?: [];
    }

    private function saveJson($data) {
        // Save current user data and chat history to the IP file
        file_put_contents($this->userFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function loadUserData() {
        // Initial structure if file doesn't exist
        $defaultData = [
            'user_data' => ['name' => '', 'email' => '', 'phone' => '', 'invoice' => '', 'satisfaction' => '', 'status' => ''], 
            'chat' => []
        ];
        if (!file_exists($this->userFile)) {
            return $defaultData;
        }
        // Use array merging to ensure 'user_data' keys exist even if file is partial
        $loaded = $this->loadJson($this->userFile);
        $loaded['user_data'] = array_merge($defaultData['user_data'], $loaded['user_data'] ?? []);
        return $loaded + $defaultData;
    }
    
    private function saveChat(&$userData, $sender, $message) {
        $userData['chat'][] = [
            'sender' => $sender,
            'time' => date('H:i'),
            'message' => $message,
        ];
        $this->saveJson($userData);
    }
    
    private function convertToProperCase($text) {
        // If the text is mostly uppercase, convert to proper case
        $upperCount = 0;
        $totalLetters = 0;
        
        // Count uppercase letters
        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            if (ctype_alpha($char)) {
                $totalLetters++;
                if (ctype_upper($char)) {
                    $upperCount++;
                }
            }
        }
        
        // If more than 80% of letters are uppercase, convert to proper case
        if ($totalLetters > 4 && ($upperCount / $totalLetters) > 0.8) {
            // Convert to lowercase first, then ucfirst for proper sentence case
            $text = strtolower($text);
            $text = preg_replace_callback('/(^|[.?!]\s*)([a-z])/', function ($matches) { return $matches[1] . strtoupper($matches[2]); }, $text);
        }
        
        return $text;
    }

    // --- Core Logic Functions ---

    public function handleRequest() {
        $action = $_POST['action'] ?? '';
        $userData = $this->loadUserData();

        if ($action === 'send') {
            $message = $_POST['message'] ?? '';
            $response = $this->processMessage($message, $userData);
            $response = $this->convertToProperCase($response);
            $this->saveChat($userData, 'b', $response);
            echo json_encode(['response' => $response, 'time' => date('H:i')]);
        } else {
            echo json_encode(['response' => 'Invalid API action.']);
        }
    }

    private function processMessage($message, &$userData) {
        // 1. Extract and Save User Data first (to prioritize data saving)
        $this->extractUserInfo($message, $userData); 

        // 2. Save User Message
        $this->saveChat($userData, 'u', $message);
        $chatHistory = $userData['chat'];
        
        // 3. Pre-processing & Normalization
        $processedMessage = strtoupper(trim($message));
        $processedMessage = preg_replace('/[^\w\s\-\'@\.]/', '', $processedMessage);
        $processedMessage = $this->applyCorrections(' ' . $processedMessage . ' ');
        
        // --- Custom Pattern Matching based on New Requirements (Priority 1) ---
        
        $lowerMessage = strtolower($message);

        // A. Name Query Check
        if ($this->contains($lowerMessage, ['my name is', 'i am', 'name'])) {
             if ($this->contains($lowerMessage, ['happy', 'bad', 'sick', 'what', 'who'])) { // not name
             } else {
                  return $this->handleNameQuery($lowerMessage, $userData);
             }
        }
        
        // B. Email Query Check
        if ($this->contains($lowerMessage, ['email', 'e-mail', 'mail'])) {
            if (!empty($userData['user_data']['email'])) {
                return "I have already noted your email: {$userData['user_data']['email']}.";
            }
            return "Your email will be stored for service purposes.";
        }
        
        // C. Phone Query Check
        if ($this->contains($lowerMessage, ['phone', 'hp', 'number', 'telp'])) {
            if (!empty($userData['user_data']['phone'])) {
                return "I have already noted your phone number: {$userData['user_data']['phone']}. Thank you.";
            }
            return "Your phone number has been recorded. Thank you.";
        }
        
        // D. Invoice Query Check
        if ($this->contains($lowerMessage, ['my invoice', 'order number', 'receipt', 'bill'])) {
            return $this->handleInvoiceQuery($lowerMessage, $userData);
        }
        
        // E. Question Check (General questions)
        if ($this->contains($lowerMessage, ['?', 'what', 'how', 'why'])) {
            if (rand(0, 99) > 80) {
                  return "That's an interesting question. Can you explain in more detail?";
            }
        }
        
        // F. Product Info Inquery
        
        // G. Delivery Info Inquery
        
        // H. Company Profile Inquery
        
        // Not in this open source version

        // --- Standard ELIZA Logic ---

        // A. Input Repeat
        if (count($chatHistory) > 1 && $message === $chatHistory[count($chatHistory) - 2]['message']) {
             return $this->getRandomResponse($this->specialPatterns['inputRepeat']);
        }
        
        // B. Spam Check
        if ($this->isSpam($message)) {
            return $this->getRandomResponse($this->specialPatterns['isSpam']);
        }
        
        // C. Pattern Match random ELIZA and NLP
        global $unknownx;
        global $ElizaPart;
        $unknownx = false;
        if (rand(0, 99) > $ElizaPart) {
            $chatbot = new SimpleChatbot('intents.json', 'responses.json');
            $response = $chatbot->getBotResponse($message);
            if ($unknownx == true) {
                $response = $this->matchPattern($processedMessage, $userData);
            }
        } else {
            $response = $this->matchPattern($processedMessage, $userData);
            if ($unknownx == true) {
                $chatbot = new SimpleChatbot('intents.json', 'responses.json');
                $response = $chatbot->getBotResponse($message);
            }
        }
        return $response;
    }

    // --- Custom Logic Functions ---

    private function contains($message, $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function handleNameQuery($message, &$userData) {
        $currentName = $userData['user_data']['name'] ?? '';
        if (!empty($currentName)) {
            return "Hello {$currentName}! Nice to meet you. How can I help you?";
        }
        $name = $this->extractName($message);
        if ($name) {
            $userData['user_data']['name'] = $name;
            $this->saveJson($userData);
            return "Hello {$name}! Nice to meet you. How can I help you?";
        }
        
        return "Could you please state your name?";
    }

    private function extractName($message) {
        // Regex to extract the name after common phrases like "i am" or "my name is"
        if (preg_match('/\b(i am|my name is)\s+(.+?)(?:\s*[\.\,\!\?]|$\s*)/', $message, $matches)) {
            $name = trim($matches[2]);
            return ucwords($name);
        }

        // Fallback: simple word extraction (similar to the provided example)
        $words = explode(' ', strtoupper($message));
        $skipWords = ['NAME', 'ME', 'IS', 'HELLO', 'HI', 'HEY', 'HALO', 'I', 'AM', 'MY', 'A', 'THE'];
        
        foreach ($words as $word) {
            $cleanedWord = strtoupper(preg_replace('/[^\w]/', '', $word));
            if (!in_array($cleanedWord, $skipWords) && strlen($cleanedWord) > 2) {
                // Return the first capitalized word found after skipping common ones
                return ucfirst(strtolower($word));
            }
        }
        return null;
    }
    
    private function handleInvoiceQuery($message, &$userData) {
        preg_match("/[0-9.]+/", $message, $matches);
        $invoice = $matches[0] ?? '';
        if (strlen($invoice) >= 1 AND strlen($invoice) <= 6) {
            $userData['user_data']['invoice'] = $invoice;
            $this->saveJson($userData);
            return "I noted your invoice number: {$invoice} . Thank you.";
        }
        
        return "Could you please state your invoice number?";
    }

    private function extractUserInfo($message, &$userData) {
        $changed = false;
        $msg = strtolower($message);

        // Extract Email (if field is empty)
        if (empty($userData['user_data']['email']) && preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/', $msg, $matches)) {
            $userData['user_data']['email'] = $matches[0];
            $changed = true;
        }

        // Extract Phone Number (using a pattern that fits 6 to 18)
        if (empty($userData['user_data']['phone']) && preg_match('/(\+?\d{1,3}\s*[\.\-]?\s*)?(\(?\d{3,4}\)?[\s\.\-]?)?\d{3}[\s\.\-]?\d{3,4}/', $msg, $matches)) {
            // Clean up the number by removing spaces/dashes before saving
            $phoneNumber = preg_replace('/[^\d\+]/', '', $matches[0]);
            if (strlen($phoneNumber) >= 6 AND strlen($phoneNumber) <= 18) { // Minimum 8 digits for a valid number
                $userData['user_data']['phone'] = $phoneNumber;
                $changed = true;
            }
        }

        if ($changed) {
             $this->saveJson($userData);
        }
    }

    // --- ELIZA ---

    private function applyCorrections($input) {
        foreach ($this->specialPatterns['correctionList'] as $correction) {
            $input = str_replace($correction[0], $correction[1], $input);
        }
        return $input;
    }

    private function applyTransposition($input) {
        foreach ($this->specialPatterns['transposList'] as $transposition) {
            $input = str_ireplace($transposition[0], $transposition[1], $input);
        }
        return $input;
    }

    private function matchPattern($message, &$userData) {
        $username = $userData['user_data']['name'] ?? 'USER';

        for ($i = 0; $i < count($this->patterns); $i++) {
            $triggerList = $this->patterns[$i][0];
            $responseList = $this->patterns[$i][1];

            foreach ($triggerList as $trigger) {
                $paddedTrigger = ' ' . strtoupper(trim($trigger)) . ' ';
                $paddedMessage = ' ' . $message . ' ';
                
                if (strpos($paddedMessage, $paddedTrigger) !== false) {
                    $response = $this->getRandomResponse($responseList);
                    
                    if (strpos($response, '{rest}') !== false) {
                        $rest = trim(str_ireplace(strtoupper(trim($trigger)), '', $message));
                        $rest = $this->applyTransposition(' ' . $rest . ' ');
                        $response = str_replace('{rest}', strtolower(trim($rest)), $response);
                    }

                    $response = str_replace('{name}', $username, $response);
                    return $response;
                }
            }
        }
        global $unknownx; $unknownx = true;
        return $this->getRandomResponse($this->specialPatterns['nullInfo']);
    }

    private function isSpam($message) {
        $words = explode(' ', trim(strtoupper(preg_replace('/[^\w\s]/', '', $message))));
        if (count($words) < 3) return false;
        
        $noSpamPrefixes = $this->specialPatterns['noSpam'];
        $nonSpamCount = 0;
        
        foreach ($words as $word) {
            $prefix = substr($word, 0, 3);
            if (in_array($prefix, $noSpamPrefixes)) {
                $nonSpamCount++;
            }
        }
        return ($nonSpamCount / count($words)) < 0.4;
    }

    private function getRandomResponse($list) {
        if (empty($list)) return "I am momentarily speechless. Please try again.";
        return $list[array_rand($list)];
    }
}

$bot = new ElizaBot();
$bot->handleRequest();
?>