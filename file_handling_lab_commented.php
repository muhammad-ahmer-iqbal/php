<?php
/*
 * BEGINNER GUIDE - PHP FILE HANDLING LAB
 * Book: Session 11, pages 253-272 (File Handling and Exception Handling).
 *
 * PHP is executed by the WEB SERVER, not directly by the web browser.
 * The server runs this PHP code, then sends the resulting HTML to the browser.
 *
 * PHP syntax reminders:
 *   $name           = a variable (a place to store data)
 *   ;               = ends a PHP statement
 *   //              = a one-line comment; PHP ignores it
 *   ["key"]         = retrieves a value from an array by its key
 *   ->              = accesses a method or property on an object
 *   ===             = compares both value AND data type
 *   ??              = supplies a default value when the left side is null/not set
 *
 * Save this as a .php file and run it through a local PHP server, such as XAMPP.
 */

// Enables stricter checks when passing values to functions with declared types.
declare(strict_types=1);

// __DIR__ means "the folder containing THIS PHP file".
// The dot (.) joins strings, creating full paths to our demo file and upload folder.
$notesFile = __DIR__ . '/example.txt';
$uploadDirectory = __DIR__ . '/uploads';

// Set default values before the visitor presses any button.
// Empty strings = no message yet; false = no error; null = no reading yet.
$message = '';              // Feedback such as "file saved" or "file missing".
$isError = false;           // Determines whether the feedback uses error styling.
$fileContent = null;        // Entire text read from example.txt.
$firstLine = null;          // First line read using fgets().
$characterPreview = null;   // First characters read using fgetc().
$uploadedImage = null;      // Path to an uploaded image (for displaying it).
$uploadDetails = [];        // [] makes an empty array for upload information.
$exceptionDetails = [];     // Stores details from a caught Exception object.
$finallyMessage = '';       // Shows that the finally block executed.

/**
 * Convert user text into safe text for an HTML page.
 * Example: <script> becomes harmless displayed text, not executable HTML.
 * "string $value" means this function accepts a string.
 * ": string" means the function returns a string.
 */
function h(string $value): string
{
    // Escaping is important whenever user-provided content appears in HTML.
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// EXAMPLE 1: die() stops the whole page immediately.
// $_GET contains values passed in the URL, e.g. ?die_demo=1.
// isset() asks "does this value exist?".
if (isset($_GET['die_demo'])) {
    // file_exists() checks whether a file/folder exists on the server.
    if (!file_exists(__DIR__ . '/missing-example.txt')) {
        // The page stops here: HTML below is NOT rendered for this request.
        die('die() stopped this page because missing-example.txt was not found. Use your browser Back button.');
    }
}

/**
 * EXAMPLE 2: Create our own exception using throw.
 * int parameters mean whole numbers; the result is declared as float.
 * An Exception represents a problem that code can catch and explain.
 */
function safeDivide(int $first, int $second): float
{
    // Dividing by 0 is not allowed. Detect it before performing the division.
    if ($second === 0) {
        // 1001 is our example's custom error code (not a PHP built-in code).
        throw new Exception('Division by zero is not allowed.', 1001);
    }

    // return sends the calculated answer back to the function's caller.
    return $first / $second;
}

// EXAMPLE 3: A single PHP page can receive its own HTML form submissions.
// $_SERVER['REQUEST_METHOD'] tells us whether this request used GET or POST.
// Only process actions after a visitor submits a form using method="post".
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // $_POST is an array holding submitted form values.
    // Every submit button below has name="action"; its value chooses what to do.
    // (string) converts the incoming value to a string; ?? '' avoids undefined-key errors.
    $action = (string) ($_POST['action'] ?? '');

    // A file handle represents an open file. Start with null (nothing is open).
    $handle = null;

    // try contains code that might fail. If we throw an Exception, catch handles it.
    try {
        // WRITE A FILE: triggered by the "Write to file" button.
        if ($action === 'write') {
            // Read the textarea and dropdown values submitted by the form.
            // trim() removes spaces and line breaks from the beginning/end.
            $note = trim((string) ($_POST['note'] ?? ''));
            $mode = (string) ($_POST['mode'] ?? 'a');

            // Validate input BEFORE touching the file.
            if ($note === '') {
                throw new Exception('Please enter some text before saving.', 101);
            }
            // strlen() counts bytes in a string; keep this teaching file small.
            if (strlen($note) > 1000) {
                throw new Exception('Please keep your text under 1000 bytes.', 102);
            }
            // Only allow two file modes: "a" (append) and "w" (overwrite).
            // in_array() checks whether the chosen mode is in the allowed list.
            // The ! symbol means NOT; true requests strict comparisons.
            if (!in_array($mode, ['a', 'w'], true)) {
                throw new Exception('Invalid file mode.', 103);
            }

            // fopen(path, mode) opens the text file and returns a file handle.
            // Mode "a" = append at the end (or create the file if missing).
            // Mode "w" = erase existing contents and write from the beginning.
            // @ suppresses PHP's normal warning; we check failure just below.
            $handle = @fopen($notesFile, $mode);
            if ($handle === false) {
                throw new Exception('Could not open example.txt for writing.', 104);
            }
            // fwrite(handle, text) saves text to the open file.
            // PHP_EOL inserts a new line appropriate for the operating system.
            // === false checks whether fwrite reported a failure.
            if (fwrite($handle, $note . PHP_EOL) === false) {
                throw new Exception('Could not write to example.txt.', 105);
            }
            // This is a ternary condition: condition ? valueIfTrue : valueIfFalse.
            // It chooses the success message based on the selected file mode.
            $message = $mode === 'a'
                ? 'Your note was appended to example.txt.'
                : 'example.txt was overwritten with your new note.';

        // READ A FILE: triggered by the "Read example.txt" button.
        } elseif ($action === 'read') {
            // is_file() checks whether this path points to an existing regular file.
            if (!is_file($notesFile)) {
                throw new Exception('example.txt does not exist yet. Write a note first.', 201);
            }
            // Mode "r" means read only. It does NOT create a missing file.
            $handle = @fopen($notesFile, 'r');
            if ($handle === false) {
                throw new Exception('Could not open example.txt for reading.', 202);
            }

            // filesize() counts the file's bytes (not its lines or words).
            $size = filesize($notesFile);
            if ($size === false || $size > 1024 * 1024) {
                throw new Exception('The file is unavailable or exceeds this demo\'s 1 MB limit.', 203);
            }
            // fread(handle, bytes) reads up to the requested number of bytes.
            // The "? :" expression avoids calling fread() on an empty file.
            // We keep the content in a variable to display later in the HTML.
            $fileContent = $size > 0 ? fread($handle, $size) : '';
            if ($fileContent === false) {
                throw new Exception('Could not read the file.', 204);
            }

            // After fread(), the file pointer is at the end of the file.
            // rewind() moves the pointer back to the beginning.
            rewind($handle);
            // fgets() reads ONE line, stopping at a newline or at end-of-file.
            $line = fgets($handle);
            // rtrim() removes the line-ending characters from this display.
            $firstLine = $line === false ? '(empty file)' : rtrim($line, "\r\n");

            // This time, read the file ONE character/byte at a time.
            rewind($handle);          // Go back to the start again.
            $characterPreview = '';   // Build a short preview of the text.
            // while repeats as long as both conditions are true:
            //   1) !feof() means "not at end-of-file".
            //   2) The preview is still shorter than 80 bytes.
            while (!feof($handle) && strlen($characterPreview) < 80) {
                $character = fgetc($handle); // Read the next byte.
                if ($character === false) {
                    break; // Stop if PHP has nothing more to read.
                }
                // .= appends another piece of text to an existing string.
                $characterPreview .= $character;
            }
            $message = 'example.txt was read successfully using three different methods.';

        // DELETE A FILE: triggered by the "Delete example.txt" button.
        } elseif ($action === 'delete') {
            // Never attempt to delete a file that does not exist.
            if (!is_file($notesFile)) {
                throw new Exception('There is no example.txt file to delete.', 301);
            }
            // unlink(path) deletes the specified file; it returns true/false.
            // This demo only allows deletion of example.txt, not arbitrary paths.
            // @ hides the normal PHP warning; we display our own message on failure.
            if (!@unlink($notesFile)) {
                throw new Exception('Could not delete example.txt.', 302);
            }
            $message = 'example.txt was deleted using unlink().';

        // UPLOAD AN IMAGE: triggered by the "Upload image" button.
        } elseif ($action === 'upload') {
            // The HTML form uses enctype="multipart/form-data" to send file bytes.
            // PHP places uploaded-file information in the $_FILES array.
            // Each file has: name, type, tmp_name, size, and error.
            if (!isset($_FILES['photo']) || !is_array($_FILES['photo'])) {
                throw new Exception('Choose an image before uploading.', 401);
            }
            // "photo" is the name of the <input type="file"> field in HTML.
            $file = $_FILES['photo'];
            // UPLOAD_ERR_OK (numeric value 0) means PHP received the upload.
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Upload failed. PHP upload error code: ' . $file['error'], 402);
            }
            // Uploaded file size is measured in bytes: 2 * 1024 * 1024 = 2 MiB.
            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception('Please select an image smaller than 2 MB.', 403);
            }
            // PHP first stores uploads in a TEMPORARY file on the server.
            // is_uploaded_file() checks that the path came from an HTTP upload.
            if (!is_uploaded_file($file['tmp_name'])) {
                throw new Exception('The temporary uploaded file is invalid.', 404);
            }

            // Security: do NOT trust the filename or the browser-provided MIME type.
            // getimagesize() examines the temporary file and identifies its image format.
            $imageInfo = @getimagesize($file['tmp_name']);
            // An associative array maps permitted image MIME types to extensions.
            // For example, a JPEG upload is saved with a .jpg extension.
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ];
            // Use the MIME type verified from the image content, if available.
            $verifiedType = $imageInfo['mime'] ?? '';
            if ($imageInfo === false || !isset($allowed[$verifiedType])) {
                throw new Exception('Only valid JPG, PNG, GIF or WebP images are allowed.', 405);
            }
            // Create an "uploads" folder if it does not already exist.
            // is_dir() checks for a folder; mkdir() creates it.
            // 0755 is a directory permission mode; true allows parent folders.
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                throw new Exception('Could not create the uploads directory.', 406);
            }

            // Create a random filename to avoid overwriting an existing upload.
            // random_bytes(8) gives random data; bin2hex() makes a readable filename.
            // The extension comes from the validated image type, not user input.
            $safeName = bin2hex(random_bytes(8)) . '.' . $allowed[$verifiedType];
            // Build the final server path and move the temporary image into it.
            $destination = $uploadDirectory . '/' . $safeName;
            // move_uploaded_file() safely moves a genuine HTTP upload.
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception('Could not move the uploaded image.', 407);
            }

            // Store a browser-friendly relative path for the <img> tag below.
            $uploadedImage = 'uploads/' . $safeName;
            // Store $_FILES details in an associative array for teaching purposes.
            // The left side is the label; the right side is the value to show.
            $uploadDetails = [
                'Original name' => (string) $file['name'],
                'Temporary file' => basename((string) $file['tmp_name']),
                'Size (bytes)' => (string) $file['size'],
                'Browser MIME type' => (string) $file['type'],
                'Verified MIME type' => $verifiedType,
                'Upload error code' => (string) $file['error'],
            ];
            $message = 'Image uploaded with move_uploaded_file().';

        // EXCEPTION DEMO: this action intentionally causes a known error.
        } elseif ($action === 'exception') {
            // Passing zero as the second argument triggers throw in safeDivide().
            // PHP then skips the rest of try and jumps to catch below.
            safeDivide(10, 0);

        } else {
            // Reject a form action that this page does not recognize.
            throw new Exception('Unrecognized action.', 999);
        }

    } catch (Exception $e) {
        // catch receives the Exception object in $e and lets the page continue.
        // Without a matching catch, an unhandled exception can stop the request.
        $isError = true;               // Tell the HTML to show error styling.
        $message = $e->getMessage();   // Get a human-readable explanation.
        // These methods give useful information about WHERE and WHY it failed:
        // getCode(): custom error code; getFile(): PHP source file path.
        // getLine(): source line number; getPrevious(): cause, if another exception.
        // Show file paths and line numbers ONLY in this local classroom demo.
        $exceptionDetails = [
            'getMessage()' => $e->getMessage(),
            'getCode()' => (string) $e->getCode(),
            'getFile()' => $e->getFile(),
            'getLine()' => (string) $e->getLine(),
            'getPrevious()' => $e->getPrevious() === null ? 'null (no previous exception)' : $e->getPrevious()->getMessage(),
        ];

    } finally {
        // finally executes after try/catch whether the action succeeded or failed.
        // It is a suitable place for CLEANUP such as closing an open file.
        if (is_resource($handle)) {
            // fopen() returned a file handle, so close it to release resources.
            fclose($handle);
        }
        $finallyMessage = 'The finally block ran; any open file handle was closed.';
    }
}
// The next PHP closing tag ends PHP; HTML markup follows below.
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP File Handling Lab - Session 11</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f5f7fb; color: #233044; font: 16px/1.55 system-ui, Arial, sans-serif; }
        header { background: #162e58; color: white; padding: 34px 20px; }
        header > div, main { max-width: 1060px; margin: auto; }
        h1 { margin: 0 0 8px; font-size: 31px; }
        header p { margin: 0; color: #d3e0f4; }
        main { padding: 24px 20px 48px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 18px; }
        section, .notice, .result { background: white; border: 1px solid #dfe5ed; border-radius: 12px; padding: 22px; margin-bottom: 18px; }
        section h2 { font-size: 20px; margin: 0 0 6px; }
        section p { color: #526178; margin: 0 0 15px; }
        textarea, select, input[type=file] { width: 100%; margin: 5px 0 14px; padding: 10px; border: 1px solid #b8c4d5; border-radius: 7px; background: white; font: inherit; }
        textarea { min-height: 105px; resize: vertical; }
        label { display: block; font-weight: 650; }
        button, .link-button { display: inline-block; color: #fff; background: #1557ad; border: 0; border-radius: 7px; padding: 10px 16px; font: inherit; cursor: pointer; text-decoration: none; }
        button:hover, .link-button:hover { background: #0e4288; }
        button.danger { background: #aa3333; }
        button.danger:hover { background: #882323; }
        code { padding: 2px 5px; background: #eff3f9; border-radius: 3px; font-size: 0.92em; }
        pre { padding: 14px; white-space: pre-wrap; overflow-wrap: anywhere; background: #f2f5fa; border-radius: 8px; }
        .notice { border-left: 5px solid #1d7e53; }
        .notice.error { border-left-color: #c23d43; }
        .notice p { margin: 6px 0 0; }
        .result h2 { margin-top: 0; font-size: 20px; }
        .small { color: #59697c; font-size: 14px; }
        .detail { display: grid; grid-template-columns: minmax(135px, 190px) 1fr; gap: 5px 14px; overflow-wrap: anywhere; }
        .detail strong { font-size: 14px; }
        .preview { max-width: 320px; max-height: 230px; display: block; object-fit: contain; margin: 15px 0; border-radius: 8px; border: 1px solid #dfe5ed; }
    </style>
</head>
<body>
<header>
    <div>
        <h1>PHP File Handling Lab</h1>
        <p>Session 11, pages 253-272: File Handling + Exception Handling</p>
    </div>
</header>
<main>
    <?php
    // HTML + PHP together: PHP decides whether the next HTML block is shown.
    // In templates, "if (...):" starts the condition and "endif;" ends it.
    // Short echo tags (an equals sign after the PHP opening tag) output a value.
    // h(...) escapes text before HTML displays it.
    ?>
    <?php if ($message !== ''): ?>
        <div class="notice<?= $isError ? ' error' : '' ?>" role="status">
            <strong><?= $isError ? 'Error caught:' : 'Success:' ?></strong> <?= h($message) ?>
            <?php if ($finallyMessage !== ''): ?><p class="small"><?= h($finallyMessage) ?></p><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php
    // Each HTML form sends a POST request BACK to this same PHP page.
    // The clicked button provides $_POST['action'] as write/read/delete/etc.
    // No form action="..." is needed because the current page is the target.
    ?>
    <div class="grid">
        <section>
            <h2>1. Create or write a file</h2>
            <p><code>fopen()</code> + <code>fwrite()</code>. Append keeps old lines; overwrite replaces them.</p>
            <form method="post">
                <label for="note">Text to save in example.txt</label>
                <textarea id="note" name="note" maxlength="1000" required placeholder="Hello PHP! This is my first note."></textarea>
                <label for="mode">File mode</label>
                <select id="mode" name="mode">
                    <option value="a">a - append to file</option>
                    <option value="w">w - overwrite file</option>
                </select>
                <button type="submit" name="action" value="write">Write to file</button>
            </form>
        </section>

        <section>
            <h2>2. Read the file</h2>
            <p><code>fread()</code> reads all bytes, <code>fgets()</code> reads one line, <code>fgetc()</code> reads characters, and <code>feof()</code> checks the end.</p>
            <form method="post"><button type="submit" name="action" value="read">Read example.txt</button></form>
        </section>

        <section>
            <h2>3. Delete the file</h2>
            <p><code>unlink()</code> removes the example.txt file created by this demo.</p>
            <form method="post"><button class="danger" type="submit" name="action" value="delete">Delete example.txt</button></form>
        </section>

        <section>
            <h2>4. Upload an image</h2>
            <p><code>$_FILES</code> holds upload information, and <code>move_uploaded_file()</code> moves the temporary image to <code>uploads/</code>.</p>
            <form method="post" enctype="multipart/form-data">
                <label for="photo">JPG, PNG, GIF or WebP (up to 2 MB)</label>
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/gif,image/webp" required>
                <button type="submit" name="action" value="upload">Upload image</button>
            </form>
        </section>

        <section>
            <h2>5. Practice exceptions</h2>
            <p>A function throws an <code>Exception</code> when dividing by zero. The <code>catch</code> block displays the error, and <code>finally</code> always runs.</p>
            <form method="post"><button type="submit" name="action" value="exception">Trigger a try/catch error</button></form>
        </section>

        <section>
            <h2>6. See how die() works</h2>
            <p><code>die()</code> stops the PHP script immediately. This differs from <code>catch</code>, which lets the page handle the problem.</p>
            <a class="link-button" href="?die_demo=1">Try die() (then click Back)</a>
        </section>
    </div>

    <?php
    // Show the reading results ONLY after the user clicks "Read example.txt".
    // null means "nothing was read yet"; an empty string means "file was empty".
    ?>
    <?php if ($fileContent !== null): ?>
        <div class="result">
            <h2>File reading results</h2>
            <strong>Entire file using fread():</strong>
            <pre><?= h($fileContent === '' ? '(empty file)' : $fileContent) ?></pre>
            <strong>First line using fgets():</strong>
            <pre><?= h((string) $firstLine) ?></pre>
            <strong>First 80 bytes using fgetc() + feof():</strong>
            <pre><?= h((string) $characterPreview) ?></pre>
        </div>
    <?php endif; ?>

    <?php
    // After an upload, display the image and the entries in the $_FILES array.
    // foreach loops through each label/value pair in $uploadDetails.
    ?>
    <?php if ($uploadedImage !== null): ?>
        <div class="result">
            <h2>Uploaded image and $_FILES data</h2>
            <img class="preview" src="<?= h($uploadedImage) ?>" alt="Uploaded image preview">
            <div class="detail">
                <?php foreach ($uploadDetails as $label => $value): ?>
                    <strong><?= h($label) ?></strong><span><?= h($value) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php
    // If catch stored exception information, show it in this section.
    // !== [] means the array is NOT empty.
    ?>
    <?php if ($exceptionDetails !== []): ?>
        <div class="result">
            <h2>Inside the Exception object</h2>
            <div class="detail">
                <?php foreach ($exceptionDetails as $label => $value): ?>
                    <strong><?= h($label) ?></strong><span><?= h($value) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    <p class="small">Learning example for local development. The page writes only example.txt and stores validated images in uploads/. Do not deploy it publicly without authentication and CSRF protection.</p>
</main>
</body>
</html>
