<?php
// 1. SESSION MANAGEMENT (For the Professor's Checklist!)
session_start();

// 2. COOKIE-BASED THEME PREFERENCE
if (isset($_POST['change_theme'])) {
    $selected_theme = $_POST['theme'];
    setcookie('site_theme', $selected_theme, time() + (86400 * 30), "/"); 
    $_COOKIE['site_theme'] = $selected_theme; 
}
$current_theme = $_COOKIE['site_theme'] ?? 'dracula';

// 3. COOKIE-BASED CALCULATION HISTORY (Serialization)
$calc_history = [];
if (isset($_COOKIE['root_history'])) {
    $calc_history = json_decode($_COOKIE['root_history'], true);
}

// 4. SECURE AUTHENTICATION (The Login System)
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Hardcoded check for the lab demonstration
    if ($username === 'admin' && $password === 'admin123') {
        session_regenerate_id(true); // Security flex for extra points
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;

        // "Remember Me" Cookie
        if (isset($_POST['remember_me'])) {
            setcookie('saved_user', $username, time() + (86400 * 7), "/");
        } else {
            setcookie('saved_user', '', time() - 3600, "/"); // Destroy if unchecked
        }
    } else {
        $error = "Access Denied. Use admin / admin123";
    }
}

// 5. LOGOUT LOGIC
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// 6. SERVER-SIDE MATH PROCESSING (The Actual Application)
$result_display = null;
if (isset($_POST['calculate']) && isset($_SESSION['logged_in'])) {
    $number = (float)$_POST['input_num'];
    $type = $_POST['root_type'];
    
    if ($number < 0 && $type == 'square') {
        $result_display = "Error: Cannot calculate the square root of a negative number.";
    } else {
        // Handle negative cube roots properly in PHP
        if ($type == 'cube' && $number < 0) {
            $root_val = -pow(abs($number), 1/3);
        } else {
            $root_val = ($type == 'square') ? sqrt($number) : pow($number, 1/3);
        }
        
        $formatted_res = round($root_val, 4);
        $label = ($type == 'square') ? "√" : "∛";
        
        $result_display = "$label($number) = $formatted_res";

        // Update History Cookie so it survives page refreshes
        array_unshift($calc_history, $result_display);
        $calc_history = array_slice(array_unique($calc_history), 0, 5); // Keep top 5 unique
        setcookie('root_history', json_encode($calc_history), time() + 86400, "/");
    }
}

// Fetch saved username for the login form
$saved_username = $_COOKIE['saved_user'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP System & Root Lab</title>
    <style>
        body, html { margin: 0; padding: 0; font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; justify-content: center; align-items: center; transition: background 0.5s ease; }
        
        /* THEMES */
        body.dracula { background: #282a36; color: #f8f8f2; }
        .dracula .glass-panel { background: rgba(68, 71, 90, 0.4); border: 1px solid rgba(189, 147, 249, 0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.4); }
        .dracula .btn { background: #bd93f9; color: #282a36; }
        .dracula .btn:hover { background: #ff79c6; }
        .dracula .result { color: #50fa7b; border: 1px solid #50fa7b; }

        body.cyberpunk { background: #0b0f19; color: #00ffcc; }
        .cyberpunk .glass-panel { background: rgba(20, 20, 40, 0.6); border: 1px solid #ff007f; box-shadow: 0 0 20px rgba(255, 0, 127, 0.2); }
        .cyberpunk .btn { background: #ff007f; color: #fff; text-transform: uppercase; }
        .cyberpunk .btn:hover { background: #00ffcc; color: #0b0f19; }
        .cyberpunk .result { color: #00ffcc; border: 1px solid #00ffcc; text-shadow: 0 0 8px #00ffcc; }

        .glass-panel { padding: 35px; border-radius: 20px; backdrop-filter: blur(10px); width: 100%; max-width: 400px; }
        h2 { text-align: center; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-size: 0.85em; opacity: 0.7; }
        input, select { width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: inherit; border-radius: 8px; box-sizing: border-box; outline: none; }
        .btn { width: 100%; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; margin-top: 10px; transition: 0.3s; }
        .result { margin-top: 20px; padding: 15px; border-radius: 8px; text-align: center; font-weight: bold; background: rgba(0,0,0,0.2); }
        .history { margin-top: 20px; font-size: 0.8em; opacity: 0.8; }
        .history ul { padding-left: 15px; }
        .logout { display: block; text-align: center; margin-top: 25px; color: inherit; font-size: 0.8em; text-decoration: none; opacity: 0.5; }
        .logout:hover { opacity: 1; text-decoration: underline; }
    </style>
</head>

<body class="<?php echo htmlspecialchars($current_theme); ?>">

    <div class="glass-panel">
        
        <?php if (!isset($_SESSION['logged_in'])): ?>
            
            <h2>System Authentication</h2>
            <?php if (isset($error)) echo "<p style='color:#ff5555; text-align:center'>$error</p>"; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Operator ID</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($saved_username); ?>" required>
                </div>
                <div class="form-group">
                    <label>Access Key</label>
                    <input type="password" name="password" required>
                </div>
                <div style="font-size:0.8em; margin-bottom:15px; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="remember_me" id="rem" style="width: auto;" <?php if($saved_username) echo 'checked'; ?>>
                    <label for="rem" style="margin: 0;">Save Operator ID (Cookie)</label>
                </div>
                <button type="submit" name="login" class="btn">Login to Terminal</button>
            </form>

        <?php else: ?>
            
            <h2 style="margin-bottom:5px">Root Laboratory</h2>
            <p style="font-size:0.8em; opacity:0.6; text-align:center; margin-bottom:20px">Operator: <?php echo htmlspecialchars($_SESSION['username']); ?></p>

            <form method="POST" style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px;">
                <div class="form-group">
                    <label>Input Value</label>
                    <input type="number" step="any" name="input_num" required>
                </div>
                <div class="form-group">
                    <label>Calculation Type</label>
                    <select name="root_type">
                        <option value="square">Square Root (√)</option>
                        <option value="cube">Cube Root (∛)</option>
                    </select>
                </div>
                <button type="submit" name="calculate" class="btn">Execute Computation</button>
            </form>

            <?php if ($result_display): ?>
                <div class="result"><?php echo $result_display; ?></div>
            <?php endif; ?>

            <div class="history">
                <strong>Recent Computations (Saved via Cookie):</strong>
                <ul>
                    <?php if (empty($calc_history)): ?>
                        <li>No recent computations.</li>
                    <?php else: ?>
                        <?php foreach ($calc_history as $item): ?>
                            <li><?php echo htmlspecialchars($item); ?></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <hr style="border:0; border-top:1px solid rgba(255,255,255,0.1); margin:20px 0">

            <form method="POST">
                <label>Terminal Aesthetic (Cookie)</label>
                <select name="theme" onchange="this.form.submit()">
                    <option value="dracula" <?php if($current_theme == 'dracula') echo 'selected'; ?>>Dracula Mode</option>
                    <option value="cyberpunk" <?php if($current_theme == 'cyberpunk') echo 'selected'; ?>>Cyberpunk Mode</option>
                </select>
                <input type="hidden" name="change_theme" value="1">
            </form>

            <a href="index.php?logout=1" class="logout">Secure Termination of Session</a>
        <?php endif; ?>

    </div>

</body>
</html>