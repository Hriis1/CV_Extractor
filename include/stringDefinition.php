<?php
function isHumanName($string)
{
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

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPhoneNumber($phone) {
    // Remove spaces, dashes, and parentheses from the phone number
    $normalizedPhone = preg_replace('/[\s\-()]+/', '', $phone);
    
    // Define a regular expression pattern for phone numbers
    $pattern = '/^(\+359|0)?8[7-9][0-9]{7}$/';

    // Check if the normalized phone number matches the pattern
    return preg_match($pattern, $normalizedPhone) === 1;
}

function isValidAddress($string) {

    if(strlen($string) >= 50) {
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
