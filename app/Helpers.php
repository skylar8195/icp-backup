<?php
use Carbon\Carbon;
use App\User;
use App\Transaction;
/*
* get url slug of page template
* @param blade name
*/

function LOGG($action,$desc) {
    $transaction = new Transaction;
    $transaction->user_id = Auth::User()->id;
    $transaction->action = $action;
    $transaction->description = $desc;
    $transaction->save();
    return true;
}

function initials($fname,$lname) {
    return substr($fname, 0, 1).substr($lname, 0, 1);
}

function transformDate($value, $format = 'm/d/Y')
{
    $value = trim($value);
    if ($value != null) {
        Carbon::useStrictMode(false);
        $date = \Carbon\Carbon::parse($value)->format('m/d/Y');
        // try {               
        //     $transformed_date = \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        //     $date = Carbon::createFromFormat('m/d/Y', $transformed_date)->format('m/d/Y');
        // } catch (\ErrorException $e) {
        //     $date = \Carbon\Carbon::createFromFormat($format, $value)->format('m/d/Y');
        // }
        if ($date) {
            return \Carbon\Carbon::createFromFormat($format, $value)->format('m/d/Y');
        } else {
            dd($value);
        }
    }
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hr',
        'i' => 'min',
        's' => 'sec',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function date_today() {
    $month = date('m');
    $day = date('d');
    $year = date('Y');
    return $year . '-' . $month . '-' . $day;
}

function date_today_mmddyyyy() {
    $month = date('m');
    $day = date('d');
    $year = date('Y');
    return $month . '/' . $day . '/' . $year;
}

function datetime_now() {
    return Carbon::now()->format('d F Y H:i');
}

function shortenString($string,$max) {
    if (strlen($string)>30) {
        return substr($string, 0, $max)."..";
    } else {
        return $string;
    }
}

function addNumberComma($string) {
    return number_format($string,0);
}

function zeroToDash($string) {
    if ($string == 0) {
        return '-';
    } else {
        return $string;
    }
}

function parameterQuotations($number) {
    if ($number < 15 && $number != 0) {
        return "background:#fac3c8;color:#dc3546;";
    } else {
        return $number;
    }
}

function parameterCV($number) {
    if ($number > .30 && $number != 0) {
        return "background:#fac3c8;color:#dc3546;";
    } else {
        return $number;
    }
}

function parameterCVnew($number) {
    if ($number > 50) {
        return "background:#000000;color:#ffffff;";
    } else if ($number <= 50 && $number > 40) {
        return "background:#fac3c8;color:#dc3546;";
    } else if ($number <= 40 && $number > 30) {
        return "background:#ffc107;color:#ffffff;";
    } else {
        return $number;
    }
}

function parameterMMRnew($number) {
    if ($number < 0.30 && $number >= 0.2) {
        return "background:#ffc107;color:#ffffff;";
    } else if ($number < 0.2 && $number >= 0.1) {
        return "background:#fac3c8;color:#dc3546;";
    } else if ($number < 0.1 && $number != "") {
        return "background:#000000;color:#ffffff;";
    } else {
        return $number;
    }
}

function parameterMMR($number) {
    if ($number < 0.1 && $number != 0) {
        return "background:#fac3c8;color:#dc3546;";
    } else {
        return $number;
    }
}

function force2digits($number) {
    if (strlen($number) == 1) {
        return '0'.$number;
    } else if (strlen($number)==0) {
        return '00';
    } else {
        return $number;
    }
}

function force3digits($number) {
    if (strlen($number)==2) {
        return '0'.$number;
    } else if (strlen($number)==1) {
        return '00'.$number;
    } else if (strlen($number)==0) {
        return '000';
    } else {
        return $number;
    }
}

function parseFreq($string) {
    if ($string == "M") {
        return "Monthly";
    } else if ($string == "Q") {
        return "Quarterly";
    } else if ($string == "S") {
        return "Semi-Annually";
    } else if ($string == "A") {
        return "Annually";
    }
}

function stripDots($string) {
    return str_replace(".","",$string);
}
?>
