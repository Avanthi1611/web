<?php
// ==========================================
// 1. CUSTOM ERROR HANDLER FOR RUNTIME ERRORS
// ==========================================
// By default, PHP throws "Warnings" or "Notices" for runtime issues, which don't stop execution.
// This function catches them and converts them into an ErrorException so we can use try/catch.
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting, so let it fall through
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// ==========================================
// 2. CUSTOM EXCEPTION FOR INVALID INPUT
// ==========================================
// Creating a specific exception class makes it easier to identify user-caused errors versus system errors.
class InvalidInputException extends Exception {}

// Variables to hold our output messages
$success_message = "";
$error_message = "";
$debug_log = "";

// ==========================================
// 3. MAIN APPLICATION LOGIC
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Grab inputs (using null coalescing operator as a fallback)
        $numerator = $_POST['numerator'] ?? null;
        $denominator = $_POST['denominator'] ?? null;

        // --- VALIDATION: Handle invalid user inputs ---
        if ($numerator === "" || $denominator === "") {
            throw new InvalidInputException("All fields are required.");
        }

        if (!is_numeric($numerator) || !is_numeric($denominator)) {
            throw new InvalidInputException("Inputs must be valid numbers. Letters and symbols are not allowed.");
        }

        // --- RUNTIME LOGIC: Handle math errors ---
        // In PHP 8+, dividing by zero automatically throws a DivisionByZeroError.
        // We can manually throw an exception to be safe across all versions.
        if ($denominator == 0) {
            throw new Exception("Mathematical Error: Cannot divide by zero.");
        }

        // Perform the calculation
        $result = $numerator / $denominator;
        $success_message = "Calculation successful: $numerator / $denominator = " . round($result, 4);

    } 
    // ==========================================
    // 4. CATCH BLOCKS (Handling specific errors)
    // ==========================================
    catch (InvalidInputException $e) {
        // Handle specifically bad user input
        $error_message = "Input Error: " . $e->getMessage();
    } 
    catch (DivisionByZeroError $e) {
        // Handle specific PHP 8+ math errors
        $error_message = "Runtime Math Error: " . $e->getMessage();
    } 
    catch (ErrorException $e) {
        // Handle the runtime warnings converted by our set_error_handler
        $error_message = "System Runtime Error: " . $e->getMessage();
    } 
    catch (Exception $e) {
        // Catch-all for any other generic exceptions
        $error_message = "General Error: " . $e->getMessage();
    } 
    finally {
        // The finally block executes no matter what happens (success or failure).
        // It is typically used to close database connections or write logs.
        $status = empty($error_message) ? "Success" : "Failed";
        $debug_log = "Execution finished at " . date("H:i:s") . ". Status: " . $status;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Error Handling App</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 40px; }
        .container { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { background-color: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background-color: #0056b3; }
        .error { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #f5c6cb; }
        .success { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #c3e6cb; }
        .log { margin-top: 20px; font-size: 0.85em; color: #666; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Secure Calculator</h2>
    <p>Enter two numbers to divide. Test the error handling by entering letters, leaving fields blank, or dividing by zero.</p>

    <?php if ($error_message): ?>
        <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="numerator">Numerator</label>
            <input type="text" name="numerator" id="numerator" placeholder="e.g., 10">
        </div>
        <div class="form-group">
            <label for="denominator">Denominator</label>
            <input type="text" name="denominator" id="denominator" placeholder="e.g., 2">
        </div>
        <button type="submit">Calculate</button>
    </form>

    <?php if ($debug_log): ?>
        <div class="log"><?php echo htmlspecialchars($debug_log); ?></div>
    <?php endif; ?>
</div>

</body>
</html>