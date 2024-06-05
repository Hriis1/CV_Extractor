<?php
require_once 'stringDefinition.php';

function extractTextArrFromDocx($filePath, $fileName)
{
    $apiKey = 'tRZEvaZOSdFGKqAjJkHGxPTBiHfHquHsYFaPLcYVPvweZPQXho';
    $url = 'https://converter.portal.ayfie.com/api/converter/1/FileConverter/Convert';

    $curl = curl_init();

    $file = new CURLFile($filePath, mime_content_type($filePath), $fileName);
    $postFields = ['file' => $file];

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => [
            "X-API-KEY: $apiKey",
            "Content-Type: multipart/form-data"
        ]
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return "cURL Error #:" . $err;
    } else {
        $responseArray = json_decode($response, true);
        return $responseArray['text'];
    }
}

function parseCVArrText($cvArrText)
{
    $cvData = [
        'personal_information' => '',
        'skills' => '',
        'experience' => '',
        'education' => '',
        'additional_info' => '',
        'names' => '',
        'email' => '',
        'phone_num' => ''
    ];

    $currSection = '';

    for ($i = 0; $i < sizeof($cvArrText); $i++) {
        $currEl = $cvArrText[$i];
        $currTrimmed = trim($currEl);
        $currTrimmedToLower = mb_strtolower($currTrimmed, 'UTF-8');

        switch ($currTrimmedToLower) {
            case 'лична информация':
            case 'personal info':
                $currSection = 'personal_information';
                break;
            case 'умения':
                $currSection = 'skills';
                break;
            case 'опит':
                $currSection = 'education';
                break;
            case 'образование':
                $currSection = 'education';
                break;
            case 'допълнителни квалификации':
            case 'допълнителна информация':
                $currSection = 'additional_info';
                break;
            case 'име':
            case 'имена':
                if ($currSection) {
                    $cvData[$currSection] += $currTrimmed + ' ';
                }
                for ($j = $i + 1; $j < 4; $j++) {
                    if (isHumanName(trim($cvArrText[$j]))) {
                        $cvData['names'] += trim($cvArrText[$j]) + ' ';
                    }
                }
                break;
            case 'email':
            case 'e-mail':
            case 'имейл':
                if ($currSection) {
                    $cvData[$currSection] += $currTrimmed + ' ';
                }
                for ($j = $i + 1; $j < 6; $j++) {

                }
                break;
            default:
                if ($currSection) {
                    $cvData[$currSection] += $currTrimmed + ' ';
                }
                break;
        }
    }
}
function extractTextFromDocxOld($filePath)
{
    // Ensure the file exists
    if (!file_exists($filePath)) {
        echo "File does not exist.";
        return false;
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
            $text = $dom->textContent;

            /* foreach ($dom->getElementsByTagName('w:t') as $element) {
                $text .= $element->nodeValue;
            } */

            return $text;
        } else {
            $zip->close();
            echo "document.xml not found in the .docx file.";
            return false;
        }
    } else {
        echo "Unable to open the .docx file.";
        return false;
    }
}

function extactCVDataFromDocxOld($filePath)
{
    $text = extractTextFromDocxOld($filePath);

    if ($text) {
        $cvData = [
            'personal_information' => '',
            'skills' => '',
            'experience' => '',
            'education' => '',
            'additional_qualifications' => ''
        ];

        // Use regular expressions to find and split sections
        $sectionHeaders = ['Умения', 'Лична информация', 'Опит', 'Образование', 'Допълнителни квалификации'];
        $regexPattern = '/\s*(' . implode('|', $sectionHeaders) . ')\s*[:\n]/u';

        // Split the text into sections based on the pattern
        $sections = preg_split($regexPattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        // Iterate over the sections and organize them into the result array
        $currentSection = null;
        for ($i = 0; $i < count($sections); $i++) {
            $part = trim($sections[$i]);
            if (in_array($part, $sectionHeaders)) {
                $currentSection = $part;
            } else {
                switch ($currentSection) {
                    case 'Умения':
                        $cvData['skills'] .= $part . "\n";
                        break;
                    case 'Лична информация':
                        $cvData['personal_information'] .= $part . "\n";
                        break;
                    case 'Опит':
                        $cvData['experience'] .= $part . "\n";
                        break;
                    case 'Образование':
                        $cvData['education'] .= $part . "\n";
                        break;
                    case 'Допълнителни квалификации':
                        $cvData['additional_qualifications'] .= $part . "\n";
                        break;
                }
            }
        }

        // Trim any extra whitespace from the sections
        foreach ($cvData as $key => $value) {
            $cvData[$key] = trim($value);
        }

        return $cvData;
    }

    return "Unable to extract data from cv";
}