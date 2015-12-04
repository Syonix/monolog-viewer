<?php
use Symfony\Component\HttpFoundation\Request;


$api = $app['controllers_factory'];
$api->get('/', function (Silex\Application $app) {
    $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);
    $clients = $viewer->getClients();
    $return = [];
    foreach($clients as $client) {
        $return [] = array(
            'name' => $client->getName(),
            'slug' => $client->getSlug(),
            'url' => BASE_URL.'api/'.$client->getSlug()
        );
    }
    $response = array(
        'clients' => $return
    );

    return $app->json($response);
});

$api->get('/{clientSlug}', function (Silex\Application $app, $clientSlug) {
    $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);
    $client = $viewer->getClient($clientSlug);
    if(null === $client) {
        $error = array('message' => 'The client was not found.');
        return $app->json($error, 404);
    }

    $logs = $client->getLogs();
    $return = [];
    foreach($logs as $log) {
        $return [] = array(
            'name' => $log->getName(),
            'slug' => $log->getSlug(),
            'url' => BASE_URL.'api/'.$client->getSlug().'/'.$log->getSlug()
        );
    }
    $response = array(
        'name' => $client->getName(),
        'slug' => $client->getSlug(),
        'logs' => $return
    );

    return $app->json($response);
});

$api->get('/{clientSlug}/{logSlug}', function (Silex\Application $app, Request $request, $clientSlug, $logSlug) {
    $defaultLimit = (intval($app['config']['default_limit']) > 0) ? intval($app['config']['default_limit']) : 100;
    $limit = intval($request->query->get('limit', $defaultLimit));
    $offset = intval($request->query->get('offset', 0));

    $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);
    $client = $viewer->getClient($clientSlug);
    if(null === $client) {
        $error = array('message' => 'The client was not found.');
        return $app->json($error, 404);
    }

    $log = $client->getLog($logSlug);
    if(null === $log) {
        $error = array('message' => 'The log file was not found.');
        return $app->json($error, 404);
    }

    $log->load();

    $logUrl = BASE_URL.'api/'.$client->getSlug().'/'.$log->getSlug();
    $totalLines = $log->countLines();

    $prevPageUrl = $offset > 0 ? ($offset-$limit < 0 ? $logUrl.'?limit='.$limit.'&offset=0' : $logUrl.'?limit='.$limit.'&offset='.($offset-$limit)) : null;
    $nextPageUrl = $offset+$limit < $totalLines ? $logUrl.'?limit='.$limit.'&offset='.($offset+$limit) : null;
    $response = array(
        'name' => $log->getName(),
        'client_name' => $client->getName(),
        'lines' => $log->getLines($limit, $offset),
        'total_lines' => $totalLines,
        'offset' => $offset,
        'limit' => $limit,
        'prev_page_url' => $prevPageUrl,
        'next_page_url' => $nextPageUrl
    );

    return $app->json($response);
});

return $api;