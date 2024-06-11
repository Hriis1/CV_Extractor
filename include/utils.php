<?php
function trimCharacters($string)
{
    //trim the curr from special characters at beggining and end
    $trimmed = preg_replace('/^[^\wА-Яа-я]+/u', '', $string);
    $trimmed = preg_replace('/[^\wА-Яа-я]+$/u', '', $trimmed);

    return $trimmed;
}