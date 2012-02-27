<?php

define('NF_APISAMPLE_BASEDIR', dirname(dirname(__FILE__)));

// include library
require_once NF_APISAMPLE_BASEDIR . '/lib/NationalField.php';

// load config
$configFile = NF_APISAMPLE_BASEDIR . '/config/config.php';

if (!file_exists($configFile)) {
    die('Looks like you forgot to create a copy of "config.php".');
}
elseif (!is_readable($configFile)) {
    die('Please make sure your copy of "config.php" can be read by PHP.');
}
else
{
    require_once $configFile;
}
