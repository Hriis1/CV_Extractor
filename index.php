<?php
require_once "include/cvExtractor.php"
    ?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>cvExtractor</title>
</head>

<body>
    <div>
        <?php
        // Example usage:
        $filePath = 'CVs/CVНенчоАДюлгеров.docx';
        $text = extractTextFromDocx($filePath);
        echo $text;
        ?>
    </div>
</body>

</html>