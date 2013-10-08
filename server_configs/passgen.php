#!/usr/bin/env php
<?

function unique_md5() {
    mt_srand(microtime(true)*100000 + memory_get_usage(true));
    return md5(uniqid(mt_rand(), true));
}

if(!defined('STDIN') )
    die ("this is a CLI app");

if ($argc == 1 || $argc > 3) 
    die ("Usage: [--user=$$] password\n\n");


// print_r($argv);

if (substr($argv[1], 0,7) == "--user=") {
    $arguser = explode("=", $argv[1]);
    $user = $arguser[1];
    $password = $argv[2];
} elseif ($argc == 3 && substr($argv[1], 0,7) != "--user=") {
    die ("Usage: [--user=$$] password\n\n");
} else {
    $password = $argv[1];
}

$cpass = crypt($password, "$1$" . unique_md5() . "$");

if (isset($user)) {
    // Do the DB stuff?
    echo "Not implemented\n\n";
} else {
    echo $cpass . "\n\n";
}
