<?php
use React\EventLoop\Loop;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Socket\Message;

//use React\Socket\Server as Reactor;

require_once dirname(__FILE__) . '/../../videos/configuration.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once $global['systemRootPath'] . 'plugin/YPTSocket/Message.php';
require_once $global['systemRootPath'] . 'objects/autoload.php';

if (!isCommandLineInterface()) {
    die("Command line only");
}

$SocketDataObj = AVideoPlugin::getDataObject("YPTSocket");
$SocketDataObj->serverVersion = YPTSocket::getServerVersion();

ob_end_flush();
_mysql_close();
session_write_close();
exec('ulimit -n 20480'); // to handle over 1 k connections
$SocketDataObj->port = intval($SocketDataObj->port);
_error_log("Starting Socket server at port {$SocketDataObj->port}");

//killProcessOnPort();
$scheme = parse_url($global['webSiteRootURL'], PHP_URL_SCHEME);
echo "Starting AVideo Socket server version {$SocketDataObj->serverVersion} on port {$SocketDataObj->port}" . PHP_EOL;

if (strtolower($scheme) !== 'https' || !empty($SocketDataObj->forceNonSecure)) {
    echo "Your socket server does NOT use a secure connection" . PHP_EOL;
    $server = IoServer::factory(
                    new HttpServer(
                            new WsServer(
                                    new Message()
                            )
                    ),
                    $SocketDataObj->port
    );

    $server->run();
} else {
    if (!file_exists($SocketDataObj->server_crt_file) || !is_readable($SocketDataObj->server_crt_file)) {
        echo "SSL ERROR, we could not access the CRT file {$SocketDataObj->server_crt_file}, try to run this command as root or use sudo " . PHP_EOL;
    }
    if (!file_exists($SocketDataObj->server_key_file) || !is_readable($SocketDataObj->server_key_file)) {
        echo "SSL ERROR, we could not access the KEY file {$SocketDataObj->server_key_file}, try to run this command as root or use sudo " . PHP_EOL;
    }

    echo "Your socket server uses a secure connection" . PHP_EOL;
    $parameters = [
        'local_cert' => $SocketDataObj->server_crt_file,
        'local_pk' => $SocketDataObj->server_key_file,
        'allow_self_signed' => $SocketDataObj->allow_self_signed, // Allow self signed certs (should be false in production)
        'verify_peer' => false,
        'verify_peer_name' => false,
        'security_level' => 0
    ];

    foreach ($parameters as $key => $value) {
        echo "Parameter [{$key}]: $value " . PHP_EOL;
    }
    echo "DO NOT CLOSE THIS TERMINAL " . PHP_EOL;
    
    $loop = React\EventLoop\Loop::get();
    
    $webSock = new React\Socket\Server($SocketDataObj->uri . ':' . $SocketDataObj->port, $loop);
    $webSock = new React\Socket\SecureServer($webSock, $loop, $parameters);
    $webServer = new Ratchet\Server\IoServer(
            new HttpServer(
                    new WsServer(
                            new Message()
                    )
            ),
            $webSock
    );
    $loop->run();
}