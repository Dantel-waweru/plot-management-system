<?php
function isRateLimited($ip) {
    $limit_file = sys_get_temp_dir() . "/rate_limit_" . md5($ip);
    $limit_data = ['count' => 0, 'last' => time()];

    if (file_exists($limit_file)) {
        $limit_data = json_decode(file_get_contents($limit_file), true);
    }

    if (time() - $limit_data['last'] > 900) { // reset every 15 minutes
        $limit_data = ['count' => 0, 'last' => time()];
    }

    $limit_data['count']++;
    $limit_data['last'] = time();

    file_put_contents($limit_file, json_encode($limit_data));

    return $limit_data['count'] > 5;
}
