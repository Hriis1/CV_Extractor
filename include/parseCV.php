<?php
require_once "cvExtractor.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) {
        $filePath = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];

        $responseArr = extractTextArrDoc($filePath, $fileName);
        //print_r($responseArr);
        $cvLang = determineLanguage($responseArr, 3);
        parseCVArrText($responseArr, 'bg', true);

    } else {
        echo 'No file uploaded.';
    }
} else {
    echo 'Invalid request method.';
}
