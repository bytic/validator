<?php

declare(strict_types=1);

function valid_url($input)
{
    $input = (string) $input;
    if (empty($input)) {
        return false;
    }
    return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $input);
}

function valid_email($email)
{
    $input = (string) $email;
    if (empty($email)) {
        return false;
    }
    $isValid = true;
    $atIndex = strrpos($email, '@');
    if (is_bool($atIndex) && !$atIndex) {
        $isValid = false;
    } else {
        $domain = substr($email, $atIndex + 1);
        $local = substr($email, 0, $atIndex);
        $localLen = strlen($local);
        $domainLen = strlen($domain);
        if ($localLen < 1 || $localLen > 64) {
            // local part length exceeded
            $isValid = false;
        } else {
            if ($domainLen < 1 || $domainLen > 255) {
                // domain part length exceeded
                $isValid = false;
            } else {
                if ('.' == $local[0] || '.' == $local[$localLen - 1]) {
                    // local part starts or ends with '.'
                    $isValid = false;
                } else {
                    if (preg_match('/\.\./', $local)) {
                        // local part has two consecutive dots
                        $isValid = false;
                    } else {
                        if (!preg_match('/^[A-Za-z0-9\-\.]+$/', $domain)) {
                            // character not valid in domain part
                            $isValid = false;
                        } else {
                            if (preg_match('/\.\./', $domain)) {
                                // domain part has two consecutive dots
                                $isValid = false;
                            } else {
                                if (!preg_match('/^(\.|[A-Za-z0-9!#%&`_=\/$\'*+?^{}|~.-])+$/',
                                    str_replace('\\', '', $local))
                                ) {
                                    // character not valid in local part unless
                                    // local part is quoted
                                    if (!preg_match('/^"(\"|[^"])+"$/', str_replace('\\', '', $local))) {
                                        $isValid = false;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($isValid && !(checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A'))) {
            // domain not found in DNS
            $isValid = false;
        }
    }

    return $isValid;
}

function valid_cc_number($cc_number)
{
    /* Validate; return value is card type if valid. */
    $card_type = '';
    $card_regexes = [
        "/^4\d{12}(\d\d\d){0,1}$/" => 'visa',
        "/^5[12345]\d{14}$/" => 'mastercard',
        "/^3[47]\d{13}$/" => 'amex',
        "/^6011\d{12}$/" => 'discover',
        "/^30[012345]\d{11}$/" => 'diners',
        "/^3[68]\d{12}$/" => 'diners',
    ];

    foreach ($card_regexes as $regex => $type) {
        if (preg_match($regex, $cc_number)) {
            $card_type = $type;
            break;
        }
    }

    if (!$card_type) {
        return false;
    }

    /*  mod 10 checksum algorithm */
    $revcode = strrev($cc_number);
    $checksum = 0;

    for ($i = 0; $i < strlen($revcode); ++$i) {
        $current_num = (int) $revcode[$i];
        if ($i & 1) {  /* Odd  position */
            $current_num *= 2;
        }
        /* Split digits and add. */
        $checksum += $current_num % 10;
        if ($current_num > 9
        ) {
            ++$checksum;
        }
    }

    if (0 == $checksum % 10) {
        return $card_type;
    } else {
        return false;
    }
}

function valid_cnp($cnp)
{
    $const = '279146358279';
    $cnp = trim($cnp);

    preg_match("|^([1256])(\d{2})(\d{2})(\d{2})(\d{6})$|ims", $cnp, $results);
    if (count($results) < 1) {
        return false;
    }

    $mf = $results[1] + 0;
    if (5 == $mf || 6 == $mf) {
        $year_add = 2000;
    } else {
        $year_add = 1900;
    }
    $year = $year_add + $results[2];
    $month = $results[3] + 0;
    $day = $results[4] + 0;

    if (!checkdate($month, $day, $year)) {
        return false;
    }

    $suma = 0;
    for ($i = 0; $i < 12; ++$i) {
        $suma += $const[$i] * $cnp[$i];
    }

    $rest = $suma % 11;

    $c13 = $cnp[12] + 0;

    if (!(($rest < 10 && $rest == $c13) || (10 == $rest && 1 == $c13))) {
        return false;
    }

    return true;
}
