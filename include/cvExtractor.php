<?php
require_once 'stringDefinition.php';
require_once 'utils.php';

enum SearchType: int
{
    case Shallow = 0;
    case Personal_Info = 2;
    case Name_Email_Phone = 3;
    case Deep = 4;
}

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

function parseCVArrText($cvArrText, $cvLang, SearchType $searchType = SearchType::Shallow)
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

    if ($searchType != SearchType::Shallow) {

        //Determine break condition
        $breakCondition = function () use (&$searchType, &$currSection, &$cvData) {
            if ($searchType == SearchType::Personal_Info) {
                return $currSection;
            } elseif ($searchType == SearchType::Name_Email_Phone) {
                return $cvData['names'] && $cvData['email'] && $cvData['phone_num'];
            } elseif ($searchType == SearchType::Deep) {
                return $currSection && $cvData['names'] && $cvData['email'] && $cvData['phone_num'];
            }
            return true;
        };

        //if personal_information has been set dont bother with it
        $currSection = $cvData['personal_information'];

        for ($i = 0; $i < sizeof($cvArrText); $i++) {
            $currEl = $cvArrText[$i];

            //trim the curr from special characters at beggining and end
            $currTrimmed = trimCharacters($currEl);

            $currTrimmedToLower = mb_strtolower($currTrimmed, 'UTF-8');

            //go over the first part of the cv again and write it as personal info if there is no personal info set
            if ($searchType == SearchType::Personal_Info || $searchType == SearchType::Deep) {
                //if a section has not yet been found
                if (!$currSection) {
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
                        case 'education and':
                            $currSection = 'education';
                            break;
                        case 'допълнителни квалификации':
                        case 'допълнителна информация':
                        case 'additional info':
                        case 'additional information':
                            $currSection = 'additional_info';
                            break;
                        default:
                            //Add to personal info
                            $cvData['personal_information'] .= $currTrimmed . ' ';
                            break;
                    }
                }
            }

            if ($searchType == SearchType::Name_Email_Phone || $searchType == SearchType::Deep) {
                //check for names if the names element is not set
                if (!$cvData['names']) {
                    $potName = $currTrimmed;
                    if (isHumanName($potName, $cvLang)) {
                        $j = $i;
                        //check for names while the next element is not a name
                        while (isHumanName($potName, $cvLang)) {
                            $cvData['names'] .= $potName . ' ';
                            $j++;
                            $potName = trimCharacters($cvArrText[$j]);
                        }
                        continue;
                    }
                }
                //check for email if the email element is not set
                if (!$cvData['email']) {
                    if (isValidEmail($currTrimmed)) {
                        $cvData['email'] = $currTrimmed;
                        continue;
                    }
                }
                //check for phone number if the phone element is not set
                if (!$cvData['phone_num']) {
                    if (isValidPhoneNumber($currEl)) {
                        $cvData['phone_num'] = $currTrimmed;
                    }
                }
            }

            //Stop iterating if everything has been found
            if ($breakCondition())
                break;
        }
    }

    echo json_encode($cvData);
    //return $cvData;
}

