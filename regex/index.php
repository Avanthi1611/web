<?php
// We will store our regex test results here to display in the UI
$results = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $results = []; // Initialize the array

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $bio = $_POST['bio'] ?? '';

    // ==========================================
    // 1. PREG_MATCH: Strict Validation
    // ==========================================
    
    // Pattern: 5 to 15 characters, only letters, numbers, and underscores
    $username_pattern = '/^[a-zA-Z0-9_]{5,15}$/';
    $results['username'] = preg_match($username_pattern, $username) 
        ? "<span class='pass'>Passed</span>" 
        : "<span class='fail'>Failed (Must be 5-15 alphanumeric chars)</span>";

    // Pattern: At least 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char
    $password_pattern = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
    $results['password'] = preg_match($password_pattern, $password) 
        ? "<span class='pass'>Passed (Strong)</span>" 
        : "<span class='fail'>Failed (Requires 8+ chars, Upper, Lower, Number, Special)</span>";

    // ==========================================
    // 2. PREG_REPLACE: Formatting & Masking
    // ==========================================
    
    // First, use regex to strip out everything that IS NOT a digit (\D means non-digit)
    $clean_phone = preg_replace('/\D/', '', $phone);
    
    // If it's exactly 10 digits, format it and mask the middle numbers for privacy
    if (strlen($clean_phone) === 10) {
        // Capture 3 groups of digits, replace with a formatted string using those groups ($1, $3)
        $mask_pattern = '/^(\d{3})(\d{3})(\d{4})$/';
        $results['phone'] = preg_replace($mask_pattern, '($1) ***-$3', $clean_phone);
    } else {
        $results['phone'] = "<span class='fail'>Failed (Must be exactly 10 digits)</span>";
    }

    // ==========================================
    // 3. PREG_MATCH_ALL: Data Extraction
    // ==========================================
    
    // Pattern: Find a literal '#' followed by 1 or more word characters (\w+)
    $hashtag_pattern = '/#(\w+)/';
    // This function populates the $matches array with everything it finds
    preg_match_all($hashtag_pattern, $bio, $matches);
    
    if (!empty($matches[0])) {
        // $matches[0] contains the full match (including the #)
        $results['tags'] = implode(", ", $matches[0]);
    } else {
        $results['tags'] = "<em>No tags found in bio.</em>";
    }
}

// Cookie-based theme retention
if (isset($_POST['change_theme'])) {
    setcookie('site_theme', $_POST['theme'], time() + (86400 * 30), "/"); 
    $_COOKIE['site_theme'] = $_POST['theme']; 
}
$current_theme = $_COOKIE['site_theme'] ?? 'cyberpunk';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ascend | Regex Terminal</title>
    <style>
        body, html { margin: 0; padding: 0; font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; justify-content: center; align-items: center; transition: background 0.5s ease; }
        
        /* THEMES */
        body.dracula { background: #282a36; color: #f8f8f2; }
        .dracula .glass-panel { background: rgba(68, 71, 90, 0.4); border: 1px solid rgba(189, 147, 249, 0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.4); }
        .dracula .btn { background: #bd93f9; color: #282a36; }
        .dracula .btn:hover { background: #ff79c6; }
        .dracula .code-box { background: rgba(0,0,0,0.3); border-left: 3px solid #ff79c6; }

        body.cyberpunk { background: #0b0f19; color: #00ffcc; }
        .cyberpunk .glass-panel { background: rgba(20, 20, 40, 0.6); border: 1px solid #ff007f; box-shadow: 0 0 20px rgba(255, 0, 127, 0.2); }
        .cyberpunk .btn { background: #ff007f; color: #fff; text-transform: uppercase; letter-spacing: 1px; }
        .cyberpunk .btn:hover { background: #00ffcc; color: #0b0f19; }
        .cyberpunk .code-box { background: rgba(0,0,0,0.4); border-left: 3px solid #00ffcc; }

        .container { display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; max-width: 900px; width: 100%; padding: 20px; }
        .glass-panel { padding: 30px; border-radius: 16px; backdrop-filter: blur(10px); flex: 1; min-width: 300px; }
        
        h2 { margin-top: 0; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;}
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-size: 0.85em; opacity: 0.8; }
        input, textarea, select { width: 100%; padding: 10px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.2); color: inherit; border-radius: 6px; box-sizing: border-box; outline: none; }
        input:focus, textarea:focus { border-color: inherit; }
        
        .btn { width: 100%; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; margin-top: 10px; transition: 0.3s; }
        
        .code-box { padding: 15px; margin-bottom: 15px; border-radius: 4px; font-family: monospace; font-size: 0.9em; line-height: 1.4;}
        .code-pattern { color: #f1fa8c; }
        .pass { color: #50fa7b; font-weight: bold; }
        .fail { color: #ff5555; font-weight: bold; }
    </style>
</head>
<body class="<?php echo htmlspecialchars($current_theme); ?>">

    <div class="container">
        <div class="glass-panel">
            <h2>User Data Input</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Username (No spaces/symbols, 5-15 chars)</label>
                    <input type="text" name="username" placeholder="e.g., alex_dev99" required>
                </div>
                <div class="form-group">
                    <label>Secure Password</label>
                    <input type="text" name="password" placeholder="e.g., P@ssw0rd123!" required>
                </div>
                <div class="form-group">
                    <label>Phone Number (Format it however you want)</label>
                    <input type="text" name="phone" placeholder="e.g., 555-123.4567 or (555) 1234567" required>
                </div>
                <div class="form-group">
                    <label>Bio (Include some #hashtags)</label>
                    <textarea name="bio" rows="3" placeholder="I love #coding and #webdev" required></textarea>
                </div>
                <button type="submit" class="btn">Process with Regex</button>
            </form>

            <form method="POST" style="margin-top: 30px;">
                <label>Terminal Theme:</label>
                <select name="theme" onchange="this.form.submit()" style="margin-top: 5px;">
                    <option value="cyberpunk" <?php if($current_theme == 'cyberpunk') echo 'selected'; ?>>Cyberpunk</option>
                    <option value="dracula" <?php if($current_theme == 'dracula') echo 'selected'; ?>>Dracula</option>
                </select>
                <input type="hidden" name="change_theme" value="1">
            </form>
        </div>

        <div class="glass-panel">
            <h2>Regex Output</h2>
            
            <?php if ($results === null): ?>
                <p style="opacity: 0.6; text-align: center; margin-top: 50px;">Awaiting terminal input...</p>
            <?php else: ?>
                
                <div class="code-box">
                    <strong>1. preg_match()</strong><br>
                    <span class="code-pattern">/^[a-zA-Z0-9_]{5,15}$/</span><br>
                    Username: <?php echo $results['username']; ?>
                </div>

                <div class="code-box">
                    <strong>2. preg_match() (Lookaheads)</strong><br>
                    <span class="code-pattern">/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/</span><br>
                    Password: <?php echo $results['password']; ?>
                </div>

                <div class="code-box">
                    <strong>3. preg_replace()</strong><br>
                    <span class="code-pattern">/^(\d{3})(\d{3})(\d{4})$/ -> '($1) ***-$3'</span><br>
                    Masked Phone: <strong><?php echo $results['phone']; ?></strong>
                </div>

                <div class="code-box">
                    <strong>4. preg_match_all()</strong><br>
                    <span class="code-pattern">/#(\w+)/</span><br>
                    Extracted Tags: <strong><?php echo $results['tags']; ?></strong>
                </div>

            <?php endif; ?>
        </div>
    </div>

</body>
</html>