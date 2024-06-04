<?php
function extractTextFromDocx($filePath) {
    // Ensure the file exists
    if (!file_exists($filePath)) {
        return "File does not exist.";
    }

    // Convert the file path to UTF-8 if it is not already
    $filePath = mb_convert_encoding($filePath, 'UTF-8', mb_detect_encoding($filePath));

    // Open the .docx file as a zip archive
    $zip = new ZipArchive();
    if ($zip->open($filePath) === true) {
        // Locate the document.xml file
        if (($index = $zip->locateName('word/document.xml')) !== false) {
            // Extract the document.xml file contents
            $data = $zip->getFromIndex($index);

            // Close the zip archive
            $zip->close();

            // Load the XML content into DOMDocument
            $dom = new DOMDocument();
            $dom->loadXML($data);

            // Extract the text content from the XML
            $text = '';
            foreach ($dom->getElementsByTagName('w:t') as $element) {
                $text .= $element->nodeValue;
            }

            return $text;
        } else {
            $zip->close();
            return "document.xml not found in the .docx file.";
        }
    } else {
        return "Unable to open the .docx file.";
    }
}

// Example usage:
$filePath = '../CVs/CVНенчоАДюлгеров.docx';
$text = extractTextFromDocx($filePath);
echo $text;
