
<?php
// Folder where files will be saved
$folder = "uploads/";

// Create folder if it doesn't exist
if (!is_dir($folder)) {
    mkdir($folder);
}

$message = "";

// 1. UPLOAD FILE
if (isset($_FILES["myFile"])) {

    $file = $_FILES["myFile"];

    // Get original file name
    $originalName = basename($file["name"]);

    // Get file name without extension
    $name = pathinfo($originalName, PATHINFO_FILENAME);

    // Get file extension
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    // Add timestamp to file name
    $fileName = $name . "_" . time() . "." . $extension;

    // Upload only allowed file types
    if ($file["error"] == 0 &&
        in_array($extension, ["jpg", "png", "pdf", "txt"])) {

        if (move_uploaded_file($file["tmp_name"], $folder . $fileName)) {
            $message = "File uploaded successfully!";
        } else {
            $message = "Upload failed!";
        }

    } else {
        $message = "Invalid file!";
    }
}

// 2. DELETE FILE
if (isset($_POST["deleteFile"])) {

    // Get the file name from the delete form
    $fileName = basename($_POST["deleteFile"]);

    // Complete path of the file
    $filePath = $folder . $fileName;

    // Check if file exists
    if (is_file($filePath)) {

        // Delete the file
        if (unlink($filePath)) {
            $message = "File deleted successfully!";
        } else {
            $message = "File deletion failed!";
        }

    } else {
        $message = "File not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<body>

<h2>File Upload and Delete</h2>

<p><?php echo htmlspecialchars($message); ?></p>

<!-- Upload Form -->
<form method="POST" enctype="multipart/form-data">

    <input type="file" name="myFile" required>

    <button type="submit">Upload</button>

</form>

<hr>

<h3>Uploaded Files</h3>

<?php
// Get all files from uploads folder
$files = scandir($folder);

foreach ($files as $fileName) {

    // Skip folders and display only files
    if (!is_file($folder . $fileName)) {
        continue;
    }

    // Make file name safe for HTML output
    $safeName = htmlspecialchars($fileName, ENT_QUOTES);

    // Display file name
    echo "<p>$safeName";

    // Create delete button for each file
    echo '<form method="POST">
            <input type="hidden" name="deleteFile" value="' . $safeName . '">
            <button type="submit">Delete</button>
          </form>';

    echo "</p>";
}
?>

</body>
</html>
