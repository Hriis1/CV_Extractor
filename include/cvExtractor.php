<?php
require_once 'stringDefinition.php';
require_once 'utils.php';

function extractTextArrDoc($filePath, $fileName)
{
    $apiKey = 'tRZEvaZOSdFGKqAjJkHGxPTBiHfHquHsYFaPLcYVPvweZPQXho';
    $url = 'https://converter.portal.ayfie.com/api/converter/1/FileConverter/Convert';

    $curl = curl_init();

    // Extract the file extension
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    // Create a new filename
    $newFileName = 'upload.' . $fileExtension;

    $file = new CURLFile($filePath, mime_content_type($filePath), $newFileName);
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

function parseCVArrText($cvArrText, $cvLang, $deepSearch = false)
{
    $cvData = [
        'personal_information' => '',
        'skills' => '',
        'experience' => '',
        'education' => '',
        'additional_info' => '',
        'names' => '',
        'email' => '',
        'phone_num' => '',
        'residence' => '',
        'driver_licence' => 'не'
    ];

    $currSection = '';

    for ($i = 0; $i < sizeof($cvArrText); $i++) {
        $currEl = $cvArrText[$i];

        //trim the curr from special characters at beggining and end
        $currTrimmed = trimCharacters($currEl);

        $currTrimmedToLower = mb_strtolower($currTrimmed, 'UTF-8');

        switch ($currTrimmedToLower) {
            case 'лична информация':
            case 'лични данни':
            case 'personal info':
            case 'personal information':
                $currSection = 'personal_information';
                break;
            case 'умения':
            case 'лични умения':
            case 'лични умения и компетенции':
            case 'лични умения и':
            case 'skills':
            case 'personal skills':
            case 'personal skills and':
            case 'personal skills and competences':
                $currSection = 'skills';
                break;
            case 'опит':
            case 'професионален опит':
            case 'трудов стаж':
            case 'work experience':
            case 'working experience':
                $currSection = 'experience';
                break;
            case 'образование':
            case 'образование и обучение':
            case 'education':
            case 'education and training':
                $currSection = 'education';
                break;
            case 'education and':
                //If curr el is 'education and' and next is 'training' the section should be education and advance iterator by 2 not 1
                $nextEl = mb_strtolower(trimCharacters($cvArrText[$i + 1]), 'UTF-8');
                if ($nextEl == 'training') {
                    $currSection = 'education';
                    $i++;
                    break;
                } else {
                    //if next is not training just do the default
                    if ($currSection) {
                        $cvData[$currSection] .= $currTrimmed . ' ';
                    }
                    break;
                }

            case 'допълнителни квалификации':
            case 'допълнителна информация':
            case 'additional info':
            case 'additional information':
                $currSection = 'additional_info';
                break;
            case 'име':
            case 'имена':
            case 'name':
            case 'names':
                if ($currSection) {
                    $cvData[$currSection] .= $currTrimmed . ' ';
                }
                for ($j = $i + 1; $j < $i + 5; $j++) {
                    $potName = trimCharacters($cvArrText[$j]);
                    if (isHumanName($potName, $cvLang)) {
                        $cvData['names'] .= $potName . ' ';
                    }
                }
                break;
            case 'имейл':
            case 'email':
            case 'e-mail':
                if ($currSection) {
                    $cvData[$currSection] .= $currTrimmed . ' ';
                }
                for ($j = $i + 1; $j < $i + 8; $j++) {
                    $potEmail = trimCharacters($cvArrText[$j]);
                    if (isValidEmail($potEmail)) {
                        $cvData['email'] = $potEmail;
                        break;
                    }
                }
                break;
            case 'телефон':
            case 'телефонен номер':
            case 'phone':
            case 'telephone':
            case 'phone number':
            case 'telephone number':
                if ($currSection) {
                    $cvData[$currSection] .= $currTrimmed . ' ';
                }
                for ($j = $i + 1; $j < $i + 8; $j++) {
                    $potPhone = $cvArrText[$j];
                    if (isValidPhoneNumber($potPhone)) {
                        $cvData['phone_num'] = $potPhone;
                        break;
                    }
                }
                break;
            case 'местожителство':
            case 'адрес':
            case 'място на живеене':
            case 'address':
            case 'location':
            case 'home address:':
            case 'current address':
            case 'residence':
                if ($currSection) {
                    $cvData[$currSection] .= $currTrimmed . ' ';
                }
                for ($j = $i + 1; $j < $i + 8; $j++) {
                    $potAddress = trimCharacters($cvArrText[$j]);
                    if (isValidAddress($potAddress)) {
                        $cvData['residence'] = $potAddress;
                    }
                }
                break;
            case 'шофьорска книжка':
            case 'свидетелство за управление на мпс':
            case 'управление на мпс':
            case 'driver licence':
            case 'drivers licence':
            case 'driving licence':
                if ($currSection) {
                    $cvData[$currSection] .= $currTrimmed . ' ';
                }
                $cvData['driver_licence'] = 'да';
                break;
            default:
                if ($currSection) {
                    $cvData[$currSection] .= $currTrimmed . ' ';
                }
                break;
        }
    }

    if ($deepSearch) {
        //go over the first part of the cv again and write it as personal info if there is no personal info set
        if ($cvData['personal_information'] == '') {
            $sectionFound = false;
            for ($i = 0; $i < sizeof($cvArrText); $i++) {
                $currEl = $cvArrText[$i];

                //trim the curr from special characters at beggining and end
                $currTrimmed = preg_replace('/^[^\wА-Яа-я]+/u', '', $currEl);
                $currTrimmed = preg_replace('/[^\wА-Яа-я]+$/u', '', $currTrimmed);

                $currTrimmedToLower = mb_strtolower($currTrimmed, 'UTF-8');

                switch ($currTrimmedToLower) {
                    case 'лична информация':
                    case 'лични данни':
                    case 'personal info':
                    case 'personal information':
                        $sectionFound = true;
                        break;
                    case 'умения':
                    case 'лични умения':
                    case 'лични умения и компетенции':
                    case 'skills':
                    case 'personal skills':
                    case 'personal skills and competences':
                        $sectionFound = true;
                        break;
                    case 'опит':
                    case 'професионален опит':
                    case 'трудов стаж':
                    case 'work experience':
                    case 'working experience':
                        $sectionFound = true;
                        break;
                    case 'образование':
                    case 'образование и обучение':
                    case 'education':
                    case 'education and training':
                        $sectionFound = true;
                        break;
                    case 'допълнителни квалификации':
                    case 'допълнителна информация':
                    case 'additional info':
                    case 'additional information':
                        $sectionFound = true;
                        break;
                    default:
                        if ($currSection) {
                            //Add to personal info
                            $cvData['personal_information'] .= $currTrimmed . ' ';
                        }
                        break;
                }

                //Stop iterating if a section is found
                if ($sectionFound)
                    break;
            }
        }


        //check the whole cv again for names email and phone_num
        $i = 0;
        while ($i < sizeof($cvArrText) && (!$cvData['names'] || !$cvData['email'] || $cvData['phone_num'])) {

            //Get the current element
            $currEl = $cvArrText[$i];

            //trim the curr from special characters at beggining and end
            $currTrimmed = trimCharacters($currEl);

            //check for names if the names element is not set
            if (!$cvData['names']) {
                $potName = $currTrimmed;
                if (isHumanName($potName, $cvLang)) {
                    //check for names while the next element is not a name
                    while (isHumanName($potName, $cvLang)) {
                        $cvData['names'] .= $potName . ' ';
                        $i++;
                        $potName = trimCharacters($cvArrText[$i]);
                    }
                    continue;
                }
            }

            //check for email if the email element is not set
            if (!$cvData['email']) {
                if (isValidEmail($currTrimmed)) {
                    $cvData['email'] = $currTrimmed;
                    $i++;
                    continue;
                }
            }

            //check for phone number if the phone element is not set
            if (!$cvData['phone_num']) {
                if (isValidPhoneNumber($currEl)) {
                    $cvData['phone_num'] = $currTrimmed;
                    $i++;
                    continue;
                }
            }

            //go to the next element
            $i++;
        }
    }

    echo json_encode($cvData);
    //return $cvData;
}

