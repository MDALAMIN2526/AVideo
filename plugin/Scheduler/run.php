<?php

//streamer config
require_once dirname(__FILE__) . '/../../videos/configuration.php';

if (!isCommandLineInterface() && !User::isAdmin()) {
    return die('Command Line only');
}

if(!AVideoPlugin::isEnabledByName('Scheduler')){
    return die('Scheduler is disabled');
}

$rowActive = Scheduler_commands::getAllActiveOrToRepeat();
$total1 = count($rowActive);

$rows = Scheduler_commands::getAllActiveAndReady();
$total2 = count($rows);

$rows2 = Scheduler_commands::getAllScheduledTORepeat();
$total3 = count($rows2);
//_log("There are {$total1} active requests; getAllActiveAndReady={$total2} getAllScheduledTORepeat={$total3} on time ". json_encode(Scheduler_commands::getTimesNow())); 

foreach ($rows as $value) {
    _log("getAllActiveAndReady run ". json_encode($value)); 
    $id = Scheduler::run($value['id']);
    if(empty($id)){
        _log("error [{$value['id']}] callbackURL={$value['callbackURL']}"); 
    }
}

foreach ($rows2 as $value) {
    _log("getAllScheduledTORepeat run ". json_encode($value)); 
    $id = Scheduler::run($value['id']);
    if(empty($id)){
        _log("error [{$value['id']}] callbackURL={$value['callbackURL']} ".json_encode($value)); 
    }
}

if($lastVisitFile = Scheduler::setLastVisit()){
    //_error_log("Last visit set {$lastVisitFile}");
}else{
    _error_log('ERROR: Last visit NOT set');
}

function _log($msg){
    
    if(!isCommandLineInterface()){
        echo date('Y-m-d H:i:s').' '.$msg.'<br>';
    }
    
    _error_log("Scheduler::run {$msg}");
}

include $global['systemRootPath'] . 'plugin/Scheduler/watchDog.php';

Scheduler::sendEmails();

AVideoPlugin::executeEveryMinute();

// This script runs every minute
$current_minute = date('i'); // Get the current minute (00-59)
$current_hour = date('G'); // Get the current hour (0-23, 24-hour format)
$current_day = date('j'); // Get the current day of the month (1-31)

// Block to execute every hour
if ($current_minute == '00') {
    AVideoPlugin::executeEveryHour();
}

// Block to execute every day (at midnight)
if ($current_hour == '0' && $current_minute == '00') {
    AVideoPlugin::executeEveryDay();
}

// Block to execute every month (at midnight)
if ($current_day == '1' && $current_hour == '0' && $current_minute == '00') {
    AVideoPlugin::executeEveryMonth();
}