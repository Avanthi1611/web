<?php
// 1. DEFINE THE FILE PATH
// This text file will be automatically created in the same folder as this script.
$data_file = 'peer_hub_logs.txt';
$message = "";

// ==========================================
// 2. STORING DATA (WRITING TO THE FILE)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_log'])) {
    
    // Sanitize user input to prevent HTML injection
    $author = htmlspecialchars(trim($_POST['author']));
    $topic = htmlspecialchars(trim($_POST['topic']));
    $content = htmlspecialchars(trim($_POST['content']));
    
    if (!empty($author) && !empty($topic) && !empty($content)) {
        // Format the data as a single string, separated by a unique delimiter (||)
        $timestamp = date('Y-m-d H:i:s');
        $record = "$timestamp||$author||$topic||$content" . PHP_EOL; // PHP_EOL adds a new line
        
        // CLASSIC FILE HANDLING: Open, Write, Close
        // 'a' stands for "Append" mode. It adds to the bottom without deleting old data.
        $handle = fopen($data_file, 'a');
        
        if ($handle) {
            fwrite($handle, $record);
            fclose($handle);
            $message = "<div class='alert success'>Log successfully committed to local storage.</div>";
        } else {
            $message = "<div class='alert error'>System Error: Unable to open file stream for writing.</div>";
        }
    } else {
        $message = "<div class='alert error'>Validation Error: All fields are required.</div>";
    }
}

// ==========================================
// 3. RETRIEVING DATA (READING FROM THE FILE)
// ==========================================
$stored_logs = [];

if (file_exists($data_file)) {
    // 'r' stands for "Read" mode. 
    $handle = fopen($data_file, 'r');
    
    if ($handle) {
        // fgets() reads the file line-by-line until it hits the end (false)
        while (($line = fgets($handle)) !== false) {
            // Only process lines that aren't completely empty
            if (trim($line) !== '') {
                // Split the string back into an array using our delimiter
                $log_data = explode('||', trim($line));
                if (count($log_data) == 4) {
                    $stored_logs[] = $log_data;
                }
            }
        }
        fclose($handle);
    }
    // Reverse the array so the newest logs appear at the top of the screen
    $stored_logs = array_reverse($stored_logs);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ascend | File Handler</title>
    <style>
        /* Modern Glassmorphism & Dark Mode Aesthetics */
        body { 
            font-family: 'Segoe UI', system-ui, sans-serif; 
            background: linear-gradient(135deg, #0b0f19 0%, #1a0b2e 100%); 
            color: #e2e8f0; 
            margin: 0; 
            padding: 40px 20px;
            min-height: 100vh;
        }
        
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; }
        }

        .glass-panel { 
            background: rgba(15, 23, 42, 0.6); 
            backdrop-filter: blur(12px); 
            border: 1px solid rgba(148, 163, 184, 0.1); 
            border-radius: 16px; 
            padding: 30px; 
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
        }

        h2 { margin-top: 0; color: #38bdf8; font-weight: 600; font-size: 1.5rem; border-bottom: 1px solid rgba(56, 189, 248, 0.2); padding-bottom: 15px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-size: 0.9rem; color: #94a3b8; }
        
        input, textarea { 
            width: 100%; 
            padding: 12px; 
            background: rgba(0, 0, 0, 0.3); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            color: white; 
            border-radius: 8px; 
            box-sizing: border-box; 
            outline: none;
            transition: border-color 0.3s;
        }
        input:focus, textarea:focus { border-color: #38bdf8; }
        
        button { 
            width: 100%; 
            background: #0284c7; 
            color: white; 
            border: none; 
            padding: 12px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: bold; 
            transition: background 0.3s; 
        }
        button:hover { background: #0369a1; }
        
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert.success { background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #34d399; }
        .alert.error { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #f87171; }
        
        .log-card { 
            background: rgba(0, 0, 0, 0.2); 
            border-left: 3px solid #38bdf8; 
            padding: 15px; 
            margin-bottom: 15px; 
            border-radius: 4px 8px 8px 4px;
        }
        .log-meta { display: flex; justify-content: space-between; font-size: 0.8rem; color: #94a3b8; margin-bottom: 8px; }
        .log-topic { font-weight: bold; color: #f8fafc; margin-bottom: 5px; }
        .log-content { font-size: 0.95rem; line-height: 1.5; color: #cbd5e1; }
        .empty-state { text-align: center; color: #64748b; font-style: italic; padding: 20px 0; }
    </style>
</head>
<body>

    <div class="container">
        <div class="glass-panel">
            <h2>Add Knowledge Log</h2>
            <?php echo $message; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Author / Peer Name</label>
                    <input type="text" name="author" required placeholder="e.g., Alex Chen">
                </div>
                <div class="form-group">
                    <label>Topic Category</label>
                    <input type="text" name="topic" required placeholder="e.g., Memory Management">
                </div>
                <div class="form-group">
                    <label>Content / Notes</label>
                    <textarea name="content" rows="5" required placeholder="Write your insight here..."></textarea>
                </div>
                <button type="submit" name="submit_log">Write to Server File</button>
            </form>
        </div>

        <div class="glass-panel" style="max-height: 600px; overflow-y: auto;">
            <h2>Offline Storage Cache</h2>
            
            <?php if (empty($stored_logs)): ?>
                <div class="empty-state">No logs found. The file is empty or does not exist yet.</div>
            <?php else: ?>
                
                <?php foreach ($stored_logs as $log): ?>
                    <div class="log-card">
                        <div class="log-meta">
                            <span>Author: <?php echo htmlspecialchars($log[1]); ?></span>
                            <span><?php echo htmlspecialchars($log[0]); ?></span>
                        </div>
                        <div class="log-topic"><?php echo htmlspecialchars($log[2]); ?></div>
                        <div class="log-content"><?php echo nl2br(htmlspecialchars($log[3])); ?></div>
                    </div>
                <?php endforeach; ?>
                
            <?php endif; ?>
        </div>
    </div>

</body>
</html>