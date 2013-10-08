<?

function unique_md5() {
    mt_srand(microtime(true)*100000 + memory_get_usage(true));
    return md5(uniqid(mt_rand(), true));
}

echo crypt($_GET['pass'], "$1$" . unique_md5() . "$");
