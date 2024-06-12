<?php
function trimCharacters($string)
{
    //trim the curr from special characters at beggining and end
    $trimmed = preg_replace('/^[^\wА-Яа-я]+/u', '', $string);
    $trimmed = preg_replace('/[^\wА-Яа-я]+$/u', '', $trimmed);

    return $trimmed;
}

function detectLanguage($string) {
    // Regular expression to match Cyrillic characters
    $cyrillicPattern = '/[\p{Cyrillic}]/u';
    // Regular expression to match Latin characters
    $latinPattern = '/[\p{Latin}]/u';
    
    // Check if the string contains Cyrillic characters
    if (preg_match($cyrillicPattern, $string)) {
        return 'bg';
    }
    
    // Check if the string contains Latin characters
    if (preg_match($latinPattern, $string)) {
        return 'en';
    }
    
    // If neither pattern matches, return unknown
    return 'unknown';
}

function hasOnlySameValues(array $arr): bool {
    // Get unique values from the array
    $uniqueValues = array_unique($arr);
    
    // If the number of unique values is 1, all elements are the same
    return count($uniqueValues) === 1;
}

function determineLanguage(array $cvArr, int $precision) : string {
    $langArr = [];
    for ($i=0; $i < $precision; $i++) {
        //Gen a random idx
        $arrIdx = mt_rand(3, sizeof($cvArr) - 3);

        //Trim the value
        $elementTrimmed = trimCharacters($cvArr[$arrIdx]);

        //Determine the language of current element
        $langArr[] = detectLanguage($elementTrimmed);
    }

    //If all elements are of the same language return the language if not try again
    if($langArr[0] != 'unknown' && hasOnlySameValues($langArr)) {
        return $langArr[0];
    } else {
        return determineLanguage($cvArr, $precision);
    }

}