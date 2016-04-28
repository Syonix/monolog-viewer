<?php
use Symfony\Component\HttpFoundation\Request;
use League\Flysystem\Adapter\Local;

$api = $app['controllers_factory'];

$api->get('/config', function (Silex\Application $app) {
    return $app->json([
        'debug'              => $app['config']['debug'],
        'timezone'           => $app['config']['timezone'],
        'date_format'        => $app['config']['date_format'],
        'display_logger'     => $app['config']['display_logger'],
        'default_limit'      => $app['config']['default_limit'],
        'reverse_line_order' => $app['config']['reverse_line_order'],
    ]);
});

$api->get('/logs', function (Silex\Application $app, Request $request) {
    $viewer = new Syonix\LogViewer\LogManager($app['config']['logs']);
    $logCollections = $viewer->getLogCollections();
    $returnLogs = (bool)$request->query->get('logs', false);
    $return = [];
    foreach ($logCollections as $logCollection) {
        $element = [
            'name' => $logCollection->getName(),
            'slug' => $logCollection->getSlug(),
            'url'  => BASE_URL . '/api/logs/' . $logCollection->getSlug()
        ];
        if ($returnLogs) {
            foreach ($logCollection->getLogs() as $log) {
                $element['logs'][] = [
                    'name' => $log->getName(),
                    'slug' => $log->getSlug(),
                    'url'  => BASE_URL . '/api/logs/' . $logCollection->getSlug() . '/' . $log->getSlug()
                ];
            }
        }
        $return[] = $element;
    }
    $response = [
        'clients' => $return
    ];

    return $app->json($response);
});

$api->get('/cache/clear', function (Silex\Application $app) {
    $cache = new \Syonix\LogViewer\LogFileCache(new Local(APP_PATH . '/cache'));
    $cache->emptyCache();

    return $app->json([
        'message' => 'success'
    ]);
});

$api->get('/logs/{clientSlug}', function (Silex\Application $app, $clientSlug) {
    $viewer = new Syonix\LogViewer\LogManager($app['config']['logs']);
    $logCollection = $viewer->getLogCollection($clientSlug);
    if (null === $logCollection) {
        $error = ['message' => 'The client was not found.'];
        return $app->json($error, 404);
    }

    $logs = $logCollection->getLogs();
    $return = [];
    foreach ($logs as $log) {
        $return [] = [
            'name' => $log->getName(),
            'slug' => $log->getSlug(),
            'url'  => BASE_URL . '/api/logs/' . $logCollection->getSlug() . '/' . $log->getSlug()
        ];
    }
    $response = [
        'name' => $logCollection->getName(),
        'slug' => $logCollection->getSlug(),
        'logs' => $return
    ];

    return $app->json($response);
});

$api->get('/logs/{clientSlug}/{logSlug}', function (Silex\Application $app, Request $request, $clientSlug, $logSlug) {
    $defaultLimit = (intval($app['config']['default_limit']) > 0) ? intval($app['config']['default_limit']) : 100;
    $limit = intval($request->query->get('limit', $defaultLimit));
    $offset = intval($request->query->get('offset', 0));
    $filter = [];
    $filter['logger'] = $request->query->get('logger');
    if ($filter['logger'] == "") $filter['logger'] = null;
    $filter['level'] = intval($request->query->get('level', 0));
    if (!($filter['level'] > 0)) $filter['level'] = null;
    $filter['text'] = $request->query->get('text');
    if ($filter['text'] == "") $filter['text'] = null;

    if ($filter['logger'] === null && $filter['level'] === null && $filter['text'] === null) {
        $filter = null;
    }

    $viewer = new Syonix\LogViewer\LogManager($app['config']['logs']);
    $logCollection = $viewer->getLogCollection($clientSlug);
    if (null === $logCollection) {
        $error = ['message' => 'The client was not found.'];
        return $app->json($error, 404);
    }

    $log = $logCollection->getLog($logSlug);
    if (null === $log) {
        $error = ['message' => 'The log file was not found.'];
        return $app->json($error, 404);
    }
    $adapter = new \League\Flysystem\Adapter\Local(APP_PATH . '/cache');
    $cache = new \Syonix\LogViewer\LogFileCache($adapter, $app['config']['cache_expire'], $app['config']['reverse_line_order']);
    $log = $cache->get($log);

    $logUrl = BASE_URL . '/api/logs/' . $logCollection->getSlug() . '/' . $log->getSlug();
    $totalLines = $log->countLines($filter);

    $prevPageUrl = $offset > 0 ? ($offset - $limit < 0 ? $logUrl . '?limit=' . $limit . '&offset=0' : $logUrl . '?limit=' . $limit . '&offset=' . ($offset - $limit)) : null;
    $nextPageUrl = $offset + $limit < $totalLines ? $logUrl . '?limit=' . $limit . '&offset=' . ($offset + $limit) : null;
    if ($filter !== null) {
        foreach ($filter as $k => $f) {
            if ($prevPageUrl !== null) $prevPageUrl .= '&' . $k . '=' . $f;
            if ($nextPageUrl !== null) $nextPageUrl .= '&' . $k . '=' . $f;
        }
    }
    $response = [
        'name'          => $log->getName(),
        'client'        => [
            'name' => $logCollection->getName(),
            'slug' => $logCollection->getSlug()
        ],
        'lines'         => $log->getLines($limit, $offset),
        'total_lines'   => $totalLines,
        'offset'        => $offset,
        'limit'         => $limit,
        'loggers'       => $log->getLoggers()->toArray(),
        'prev_page_url' => $prevPageUrl,
        'next_page_url' => $nextPageUrl
    ];

    return $app->json($response);
});

return $api;
