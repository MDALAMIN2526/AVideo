<?php
$configFile = '../../videos/configuration.php';
if (!file_exists($configFile)) {
    [$scriptPath] = get_included_files();
    $path = pathinfo($scriptPath);
    $configFile = $path['dirname'] . "/" . $configFile;
}
$global['bypassSameDomainCheck'] = 1;
$remember = 1; 
require_once $configFile;
require_once $global['systemRootPath'] . 'plugin/API/API.php';
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");

$plugin = AVideoPlugin::loadPluginIfEnabled("API");
$objData = AVideoPlugin::getObjectDataIfEnabled("API");

if (empty($plugin)) {
    $obj = new ApiObject("API Plugin disabled");
    die(_json_encode($obj));
}

// gettig the mobile submited value
$inputJSON = url_get_contents('php://input');
$input = _json_decode($inputJSON, true); //convert JSON into array
if (empty($input)) {
    $input = [];
} else {
    $input = object_to_array($input);
}

$_REQUEST['rowCount'] = $_GET['rowCount'] = getRowCount();

$parameters = array_merge($_GET, $_POST, $input);

$obj = $plugin->get($parameters);
if (is_object($obj)) {
    $obj = _json_encode($obj);
}

header('Content-Type: application/json');
if (!empty($_REQUEST['gzip'])) {
    $obj = gzencode($obj, 9);
    header('Content-Encoding: gzip');
}

header('Content-Length: ' . strlen($obj));
die($obj);
