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
            return 'true';
        }
    }

    return 'false';
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}