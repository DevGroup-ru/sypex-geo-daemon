<?php
require 'vendor/autoload.php';

use Ulrichsg\Getopt\Argument;
use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;


// SxGeo class is not a composer package =(
require 'SxGeo.php';

$getopt = new Getopt([
    (new Option('h', 'host', Getopt::REQUIRED_ARGUMENT))
        ->setDefaultValue('127.0.0.1')
        ->setDescription('IP address or host to listen on'),
    (new Option('p', 'port', Getopt::REQUIRED_ARGUMENT))
        ->setArgument(new Argument(16001, 'is_numeric'))
        ->setDescription('Port to listen on'),
    (new Option('f', 'file', Getopt::REQUIRED_ARGUMENT))
        ->setDefaultValue('SxGeoCity.dat')
        ->setDescription('SypexGeo data file(city or country).'),
    (new Option('v', 'verbose'))
        ->setDescription('Be verbose - log each good request to stdout'),
    (new Option(null, 'help'))
        ->setDescription('Display help information'),
]);

try {
    $getopt->parse();

    if ($getopt['help']) {
        echo $getopt->getHelpText();
        exit(0);
    }

    list($host, $port, $file) = [$getopt['host'], $getopt['port'], $getopt['file']];

    // Creates SxGeo class with the best performance flags for geo daemon

    if (file_exists($file) === false || is_readable($file) === false) {
        echo "Unable to open SypexGEO data file: \"$file\"\n";
        exit(1);
    }

    try {
        $SxGeo = new SxGeo($file, SXGEO_BATCH | SXGEO_MEMORY);
    } catch (Exception $e) {
        echo "Unable to create SypexGEO class on data file: \"$file\"\n";
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }

    $app = function (\React\Http\Request $request, \React\Http\Response $response) use ($SxGeo) {

        // log time
        $time_start = microtime(true);

        // $_GET params of query
        $get = $request->getQuery();

        // retrieve ip param
        $ip = isset($get['ip']) ? (string) $get['ip'] : false;

        if ($ip !== false) {
            echo "Got request for ip: '$ip'\n";
            try {
                $response->writeHead(200, array('Content-Type' => 'application/json'));
                $data = $SxGeo->getCityFull($ip);
                $time_end = microtime(true);
                $data['time'] = number_format($time_end - $time_start, 12);
                $data['error'] = false;
                $response->end(json_encode($data));
            } catch (\Exception $e) {
                // on any error throw 500 and the whole error message
                $response->writeHead(500, array('Content-Type' => 'application/json'));
                $data = [
                    'error' => true,
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'code' => $e->getCode(),
                ];
                $time_end = microtime(true);
                $data['time'] = number_format($time_end - $time_start, 12);
                $response->end(json_encode($data));
            }
        } elseif (isset($get['ping'])) {
            $response->writeHead(200, array('Content-Type' => 'text/html'));
            $response->end('pong');
        } elseif (isset($get['ping-json'])) {
            $response->writeHead(200, array('Content-Type' => 'application/json'));
            $response->end(json_encode([
                'error' => false,
                'success' => true,
                'message' => 'pong',
            ]));
        } else {
            // if ip is not provided
            $response->writeHead(400, array('Content-Type' => 'application/json'));
            $response->end(json_encode([
                'error' => true,
                'message' => 'Bad arguments',
            ]));
        }
    };

    // create event loop, socket and HTTP server
    $loop = React\EventLoop\Factory::create();
    $socket = new React\Socket\Server($loop);
    $http = new React\Http\Server($socket, $loop);

    $http->on('request', $app);
    echo "Starting server at http://$host:$port\n";
    echo "You can check an example JSON response: http://$host:$port/?ip=213.180.204.3\n";

    $socket->listen($port, $host);
    $loop->run();

} catch (UnexpectedValueException $e) {
    echo "Error: ".$e->getMessage()."\n";
    echo $getopt->getHelpText();
    exit(1);
} catch (Exception $e) {
    echo "Exception: ".$e->getMessage()."\n";
    exit(2);
}
