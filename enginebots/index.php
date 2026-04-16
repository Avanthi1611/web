<?php
session_start();

// 1. THE KNOWLEDGE BASE (Our Database)
// A simple associative array acting as the bot's brain.
$knowledge_base = [
    'hello' => 'Greetings, Operator. How can I assist you in the Ascend platform today?',
    'help' => 'I can provide information on modules, rules, or system status. What do you need?',
    'rules' => 'Ascend Rule 1: Always sanitize inputs. Ascend Rule 2: Never trust client-side data.',
    'modules' => 'Currently active modules: PHP Backend, JS Frontend, and Database Architecture.',
    'status' => 'All Ascend servers are currently operational and running at 100% capacity.'
];

// 2. INITIALIZE CHAT HISTORY
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [
        ['sender' => 'bot', 'text' => 'System initialized. Type a query (e.g., "help", "rules", "modules").']
    ];
}

// 3. THE "INTELLIGENCE" ENGINE
function getIntelligentResponse($user_input, $kb) {
    // Clean and normalize the input
    $clean_input = strtolower(trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $user_input)));
    $words = explode(' ', $clean_input);

    // Step A: Check for Exact Keyword Matches first
    foreach ($words as $word) {
        if (array_key_exists($word, $kb)) {
            return $kb[$word];
        }
    }

    // Step B: Fuzzy Matching (The "Intelligence" part using Levenshtein Distance)
    // If no exact match is found, check if the user made a typo.
    $closest_word = "";
    $shortest_distance = -1;

    foreach ($words as $word) {
        foreach ($kb as $key => $answer) {
            // Calculate how many typos exist between the user's word and our knowledge base keys
            $distance = levenshtein($word, $key);

            // If distance is 0, it's an exact match (caught above), but if it's 1 or 2, it's a likely typo
            if ($distance <= 2) {
                if ($shortest_distance < 0 || $distance < $shortest_distance) {
                    $closest_word = $key;
                    $shortest_distance = $distance;
                }
            }
        }
    }

    // If we found a close match, return it and tell the user we autocorrected them
    if ($closest_word !== "") {
        return "<em>(Did you mean '$closest_word'?)</em><br>" . $kb[$closest_word];
    }

    // Step C: Fallback Response
    $available_topics = implode(", ", array_keys($kb));
    return "I do not understand that query. I am currently trained on the following topics: <strong>$available_topics</strong>.";
}

// 4. HANDLE USER INPUT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_message'])) {
    $user_message = htmlspecialchars(trim($_POST['user_message']));
    
    if (!empty($user_message)) {
        // Add user message to history
        $_SESSION['chat_history'][] = ['sender' => 'user', 'text' => $user_message];
        
        // Generate and add bot response to history
        $bot_response = getIntelligentResponse($user_message, $knowledge_base);
        $_SESSION['chat_history'][] = ['sender' => 'bot', 'text' => $bot_response];
    }
    
    // Redirect to prevent form resubmission on page refresh
    header("Location: index.php");
    exit;
}

// 5. CLEAR CHAT
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    unset($_SESSION['chat_history']);
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ascend | Intelligent Bot</title>
    <style>
        /* Cyberpunk Terminal Aesthetic */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #0b0f19; color: #00ffcc; margin: 0; padding: 20px; display: flex; justify-content: center; height: 100vh; box-sizing: border-box; }
        
        .chat-container { width: 100%; max-width: 500px; background: rgba(20, 20, 40, 0.9); border: 1px solid #ff007f; border-radius: 12px; display: flex; flex-direction: column; box-shadow: 0 0 20px rgba(255, 0, 127, 0.2); overflow: hidden; }
        
        .chat-header { background: #ff007f; color: white; padding: 15px; text-align: center; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; position: relative; }
        .clear-btn { position: absolute; right: 15px; top: 15px; color: white; text-decoration: none; font-size: 0.8em; opacity: 0.8; }
        .clear-btn:hover { opacity: 1; }

        .chat-window { flex-grow: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; }
        
        .message { max-width: 80%; padding: 10px 15px; border-radius: 8px; line-height: 1.4; font-size: 0.95em; }
        .msg-bot { background: rgba(0, 255, 204, 0.1); border-left: 3px solid #00ffcc; align-self: flex-start; color: #e2e8f0; }
        .msg-user { background: rgba(255, 0, 127, 0.1); border-right: 3px solid #ff007f; align-self: flex-end; color: #fff; text-align: right; }

        .input-area { padding: 15px; background: rgba(0, 0, 0, 0.3); border-top: 1px solid rgba(255, 255, 255, 0.1); display: flex; gap: 10px; }
        input[type="text"] { flex-grow: 1; padding: 12px; background: #0b0f19; border: 1px solid #00ffcc; color: white; border-radius: 6px; outline: none; }
        button { background: #00ffcc; color: #0b0f19; border: none; padding: 0 20px; font-weight: bold; border-radius: 6px; cursor: pointer; text-transform: uppercase; }
        button:hover { background: #ff007f; color: white; }
    </style>
</head>
<body>

    <div class="chat-container">
        <div class="chat-header">
            Ascend AI Terminal
            <a href="index.php?action=clear" class="clear-btn">Clear Data</a>
        </div>

        <div class="chat-window" id="chatWindow">
            <?php foreach ($_SESSION['chat_history'] as $msg): ?>
                <div class="message <?php echo $msg['sender'] === 'bot' ? 'msg-bot' : 'msg-user'; ?>">
                    <?php echo $msg['text']; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <form class="input-area" method="POST" action="index.php">
            <input type="text" name="user_message" placeholder="Type your query..." required autocomplete="off" autofocus>
            <button type="submit">Send</button>
        </form>
    </div>

    <script>
        const chatWindow = document.getElementById('chatWindow');
        chatWindow.scrollTop = chatWindow.scrollHeight;
    </script>

</body>
</html>