<?php
require_once 'stringDefinition.php';

function extractTextArrDoc($filePath, $fileName)
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

function parseCVArrText($cvArrText, $deepSearch = false)
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
        $currTrimmed = preg_replace('/^[^\wА-Яа-я]+/u', '', $currEl);
        $currTrimmed = preg_replace('/[^\wА-Яа-я]+$/u', '', $currTrimmed);

        $currTrimmedToLower = mb_strtolower($currTrimmed, 'UTF-8');

        switch ($currTrimmedToLower) {
            case 'лична информация':
            case 'personal info':
                $currSection = 'personal_information';
                break;
            case 'умения':
            case 'лични умения':
            case 'лични умения и компетенции':
                $currSection = 'skills';
                break;
            case 'опит':
            case 'професионален опит':
            case 'трудов стаж':
                $currSection = 'experience';
                break;
            case 'образование':
            case 'образование и обучение':
                $currSection = 'education';
                break;
            case 'допълнителни квалификации':
            case 'допълнителна информация':
                $currSection = 'additional_info';
                break;
            case 'име':
            case 'имена':
                if ($currSection) {
                    $cvData[$currSection] .= $currTrimmed . ' ';
                }
                for ($j = $i + 1; $j < $i + 5; $j++) {
                    if (isHumanName(trim($cvArrText[$j]))) {
                        $cvData['names'] .= trim($cvArrText[$j]) . ' ';
                    }
                }
                break;
            case 'email':
            case 'e-mail':
            case 'имейл':
                if ($currSection) {
                    $cvData[$currSection] .= $currTrimmed . ' ';
                }
                for ($j = $i + 1; $j < $i + 7; $j++) {
                    $potEmail = trim($cvArrText[$j]);
                    if (isValidEmail($potEmail)) {
                        $cvData['email'] = $potEmail;
                        break;
                    }
                }
                break;
            case 'телефон':
            case 'телефонен номер':
                if ($currSection) {
                    $cvData[$currSection] .= $currTrimmed . ' ';
                }
                for ($j = $i + 1; $j < $i + 7; $j++) {
                    $potPhone = trim($cvArrText[$j]);
                    if (isValidPhoneNumber($potPhone)) {
                        $cvData['phone_num'] = $potPhone;
                        break;
                    }
                }
                break;
            case 'местожителство':
            case 'адрес':
            case 'място на живеене':
                if ($currSection) {
                    $cvData[$currSection] .= $currTrimmed . ' ';
                }
                for ($j = $i + 1; $j < $i + 7; $j++) {
                    $potAddress = trim($cvArrText[$j]);
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
                    case 'personal info':
                        $sectionFound = true;
                        break;
                    case 'умения':
                    case 'лични умения':
                    case 'лични умения и компетенции':
                        $sectionFound = true;
                        break;
                    case 'опит':
                    case 'професионален опит':
                    case 'трудов стаж':
                        $sectionFound = true;
                        break;
                    case 'образование':
                    case 'образование и обучение':
                        $sectionFound = true;
                        break;
                    case 'допълнителни квалификации':
                    case 'допълнителна информация':
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
    }

    echo json_encode($cvData);
    //return $cvData;
}

