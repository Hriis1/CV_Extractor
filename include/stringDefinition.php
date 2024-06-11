<?php

function is_word_wiktionary($word, $lang)
{
    //word to lower case
    $word = mb_strtolower($word, 'UTF-8');
    // Construct the URL for the Wiktionary API
    $url = "https://$lang.wiktionary.org/w/api.php?action=query&titles=" . urlencode($word) . "&prop=extracts&explaintext&format=json&exintro&redirects=1";

    // Use file_get_contents to send the GET request
    $response = file_get_contents($url);

    // Decode the JSON response
    $data = json_decode($response, true);

    // Check if the response contains the 'extract' field
    if (isset($data['query']['pages'])) {
        $pages = $data['query']['pages'];
        $page = reset($pages); // Get the first element of the pages array
        return isset($page['extract']);
    }

    return false;
}
function isHumanName($string, $lang)
{
    $wordToLower = mb_strtolower($string, 'UTF-8');

    //additional words not in the dictionary and countries 
    $additionalWords = [
        //Words
        'телефон', 'телефони',

        //Countries
        'българия','bulgaria',
        'америка','america',
        'сащ', 'usa',
        'германия', 'germany',
        'франция', 'france',
        'италия', 'italy',
        'испания', 'spain',
        'португалия', 'portugal',
        'гърция', 'greece',
        'турция', 'turkey',
        'русия', 'russia',
        'китай', 'china',
        'япония', 'japan',
        'индия', 'india',
        'бразилия', 'brazil',
        'мексико', 'mexico',
        'канада', 'canada',
        'австралия', 'australia',
        'аржентина', 'argentina',
        'египет', 'egypt'
    ];

    //if its in the additional words
    if (in_array($wordToLower, $additionalWords)) {
        return false;
    }

     //if its a word its not a name
     if (is_word_wiktionary($string, $lang)) {
        return false;
    }

    // Define common patterns that could indicate a human name
    // Including common endings for names and surnames in Cyrillic and Latin scripts
    $namePatterns = [
        '/^[А-ЯЁ][а-яё]+(?:[ \-][А-ЯЁ][а-яё]+)*$/u', // Cyrillic names (e.g., Атанас, Атанасов, Атанас Атанасов)
        '/^[A-Z][a-z]+(?:[ \-][A-Z][a-z]+)*$/',      // Latin names (e.g., John, Smith, John Smith)
    ];

    // Check if the string matches any of the name patterns
    foreach ($namePatterns as $pattern) {
        if (preg_match($pattern, $string)) {
            return true;
        }
    }

    return false;
}

function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPhoneNumber($phone)
{
    // Remove spaces, dashes, and parentheses from the phone number
    $normalizedPhone = preg_replace('/[\s\-()]+/', '', $phone);

    // Define a regular expression pattern for phone numbers
    $pattern = '/^(\+359|0)?8[7-9][0-9]{7}$/';

    // Check if the normalized phone number matches the pattern
    return preg_match($pattern, $normalizedPhone) === 1;
}

function isValidAddress($string)
{

    if (strlen($string) >= 50) {
        return false;
    }
    // Define common patterns that could indicate an address
    $addressPatterns = [
        '/(ул\.|ж\.к\.|гр\.|пл\.|бул\.|обл\.|общ\.)/u', // Common address abbreviations in Bulgarian (e.g., ул., ж.к., гр., пл., бул., обл., общ.)
        '/\b(street|st\.|avenue|ave\.|road|rd\.|city|town|zip|postal|code|place|plaza)\b/i', // Common address terms in English
        '/\b[0-9]{3,5}\b/', // Postal codes (typically 3 to 5 digits)
        '/\b\d{1,4}[A-Za-z]?\b/', // House numbers (e.g., 523A)
    ];

    // Normalize the string to remove unnecessary spaces
    $normalizedString = trim(preg_replace('/\s+/', ' ', $string));


    // Check if the string matches any of the address patterns
    foreach ($addressPatterns as $pattern) {
        if (preg_match($pattern, $normalizedString)) {
            return true;
        }
    }
    return false;
}
