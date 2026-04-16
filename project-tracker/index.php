<?php
// 1. START THE SESSION
// This must be the very first thing in your PHP file. 
// It allows us to remember if the user is logged in across different pages.
session_start();

// 2. DEFINE OUR "DATABASE" FILE
// We are using a simple JSON file to store data offline without needing MySQL.
$dataFile = 'projects.json';

// If the file doesn't exist yet, PHP will create it with an empty array.
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}

// 3. HANDLE LOGIN FORM (POST REQUEST)
// Check if the login form was submitted
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Hardcoded credentials for lab demonstration
    if ($username === 'admin' && $password === 'password123') {
        $_SESSION['logged_in'] = true;
        $_SESSION['user'] = $username;
    } else {
        $loginError = "Invalid credentials. Try admin / password123";
    }
}

// 4. HANDLE LOGOUT (GET REQUEST)
// Check if the user clicked the logout link (e.g., index.php?action=logout)
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy(); // Wipe the session memory
    header("Location: index.php"); // Redirect back to the main page
    exit;
}

// 5. HANDLE ADDING A NEW PROJECT (POST REQUEST)
if (isset($_POST['add_project']) && isset($_SESSION['logged_in'])) {
    // Sanitize input to prevent malicious code from breaking our app
    $title = htmlspecialchars($_POST['title']);
    $techStack = htmlspecialchars($_POST['tech_stack']);
    
    if (!empty($title) && !empty($techStack)) {
        // Read the current data from our JSON file
        $currentData = json_decode(file_get_contents($dataFile), true);
        
        // Create a new project array
        $newProject = [
            'id' => time(), // Use timestamp as a unique ID
            'title' => $title,
            'tech_stack' => $techStack,
            'status' => 'Pending'
        ];
        
        // Append the new project to our data array
        $currentData[] = $newProject;
        
        // Save it back to the JSON file
        file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT));
    }
}

// 6. HANDLE DELETING A PROJECT (GET REQUEST)
if (isset($_GET['delete']) && isset($_SESSION['logged_in'])) {
    $idToDelete = $_GET['delete'];
    $currentData = json_decode(file_get_contents($dataFile), true);
    
    // Filter out the project with the matching ID
    $updatedData = array_filter($currentData, function($project) use ($idToDelete) {
        return $project['id'] != $idToDelete;
    });
    
    // Save the updated array back to the file
    file_put_contents($dataFile, json_encode(array_values($updatedData), JSON_PRETTY_PRINT));
    
    // Redirect to clear the URL parameters
    header("Location: index.php");
    exit;
}

// 7. FETCH ALL PROJECTS TO DISPLAY
$projects = json_decode(file_get_contents($dataFile), true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP Project Tracker</title>
    <style>
        /* A dark mode aesthetic (Dracula/Cyberpunk vibe) */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #282a36; color: #f8f8f2; margin: 0; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; background: #44475a; padding: 30px; border-radius: 10px; box-shadow: 0 8px 16px rgba(0,0,0,0.5); }
        h1, h2 { color: #bd93f9; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; background: #282a36; border: 1px solid #6272a4; color: white; border-radius: 5px; box-sizing: border-box; }
        button { background-color: #50fa7b; color: #282a36; border: none; padding: 10px 20px; cursor: pointer; font-weight: bold; border-radius: 5px; }
        button:hover { background-color: #5af182; }
        .error { color: #ff5555; margin-bottom: 15px; }
        .project-card { background: #282a36; padding: 15px; margin-bottom: 15px; border-radius: 5px; border-left: 4px solid #ff79c6; display: flex; justify-content: space-between; align-items: center; }
        .delete-btn { background-color: #ff5555; color: white; text-decoration: none; padding: 5px 10px; border-radius: 3px; font-size: 14px; }
        .delete-btn:hover { background-color: #ff4444; }
        .logout-link { float: right; color: #8be9fd; text-decoration: none; }
    </style>
</head>
<body>

<div class="container">
    <?php if (!isset($_SESSION['logged_in'])): ?>
        
        <h1>System Login</h1>
        <?php if (isset($loginError)) echo "<div class='error'>$loginError</div>"; ?>
        
        <form method="POST" action="index.php">
            <label>Username (admin)</label>
            <input type="text" name="username" required>
            
            <label>Password (password123)</label>
            <input type="password" name="password" required>
            
            <button type="submit" name="login">Log In</button>
        </form>

    <?php else: ?>
        
        <a href="index.php?action=logout" class="logout-link">Log Out</a>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?>!</h1>
        
        <h2>Add New Project</h2>
        <form method="POST" action="index.php">
            <input type="text" name="title" placeholder="Project Title (e.g., Peer Hub UI)" required>
            <input type="text" name="tech_stack" placeholder="Tech Stack (e.g., React, Tailwind, PHP)" required>
            <button type="submit" name="add_project">Save Project</button>
        </form>

        <hr style="border-color: #6272a4; margin: 30px 0;">

        <h2>Saved Projects</h2>
        <div id="project-list">
            <?php if (empty($projects)): ?>
                <p>No projects saved yet.</p>
            <?php else: ?>
                <?php foreach ($projects as $proj): ?>
                    <div class="project-card">
                        <div>
                            <strong><?php echo htmlspecialchars($proj['title']); ?></strong><br>
                            <small style="color: #8be9fd;">Stack: <?php echo htmlspecialchars($proj['tech_stack']); ?></small>
                        </div>
                        <a href="index.php?delete=<?php echo $proj['id']; ?>" class="delete-btn">Delete</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

</body>
</html>