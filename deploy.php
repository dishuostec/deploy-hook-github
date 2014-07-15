<?php
define('PROJECTS_DIR', '/data/website');

$github_delivery = isset($_SERVER['HTTP_X_GITHUB_DELIVERY'])
    ? $_SERVER['HTTP_X_GITHUB_DELIVERY'] : NULL;
$github_event = isset($_SERVER['HTTP_X_GITHUB_EVENT'])
    ? $_SERVER['HTTP_X_GITHUB_EVENT'] : NULL;

if (is_null($github_delivery)) {
    echo 'invalid request';
    exit;
}

if ($github_event !== 'push') {
    echo $github_event;
    exit;
}

$data = json_decode(file_get_contents('php://input'));

$project = $data->repository->name;
$ref = $data->ref;
$branch = substr($ref, 11);
define('WORKING_DIR', PROJECTS_DIR.'/'.$project.'/.repo');
define('TARGET_DIR', PROJECTS_DIR.'/'.$project.'/'.$branch);

if (! is_dir(WORKING_DIR)) {
    printf('dir not exist: %s', WORKING_DIR);
    exit;
}

chdir(WORKING_DIR);

$commands = array(
    'echo $PWD',
    'whoami',
    'git fetch origin '.$ref,
    //'git reset --hard origin/'.$branch,
    //'git status',
    'git show --summary',
    //'git submodule sync',
    //'git submodule update',
    //'git submodule status',
    'mkdir '.TARGET_DIR,
    'git archive '.$branch.' | tar -x -C '.TARGET_DIR,
);

$output = '';
foreach ($commands AS $command) {
    // Run it
    $tmp = shell_exec($command);
    // Output
    $output .= "=== {$command} ===\n";
    $output .= $tmp."\n";
}

echo $output;
