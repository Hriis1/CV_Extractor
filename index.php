<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload and Extract Text</title>
</head>
<body>
    <form action="include/parseCV.php" method="post" enctype="multipart/form-data">
        <input type="file" name="file" id="fileInput" required>
        <button type="submit">Upload and Parse CV</button>
    </form>
</body>
</html>
