<?php
require_once "cvExtractor.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) {
        $filePath = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];

        $responseArr = extractTextArrDoc($filePath, $fileName);
        $cvLang = determineLanguage($responseArr, 3);
        parseCVArrText($responseArr, $cvLang, SearchType::Deep);

        //print_r($responseArr);

    } else {
        echo 'No file uploaded.';
    }
} else {
    echo 'Invalid request method.';
}
