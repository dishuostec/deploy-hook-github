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
define('GIT_DIR', PROJECTS_DIR.'/'.$project.'/.repo');
define('GIT', 'git --git-dir '.GIT_DIR.' ');
define('WORK_DIR', PROJECTS_DIR.'/'.$project.'/'.$branch);

if (! is_dir(GIT_DIR)) {
    printf('dir not exist: %s', GIT_DIR);
    exit;
}

$work_dir_exist = is_dir(WORK_DIR);
if (! $work_dir_exist) {
    mkdir(WORK_DIR, 755, TRUE);
}

chdir(WORK_DIR);

$commands = array(
    'echo $PWD',
    //'whoami',
    GIT.'fetch origin '.$ref,
    GIT.'show --summary',
);

if ($work_dir_exist) {
    // delete file
    foreach ($data->commits as $commit) {
        $commands[] = sprintf('%s diff-tree --name-only --no-commit-id --diff-filter=D -r %s | xargs rm -f',
            GIT, $commit->id);
    }
}

$commands[] = GIT.'archive '.$branch.' | tar -x -C '.WORK_DIR;

$output = '';
foreach ($commands AS $command) {
    // Run it
    $tmp = shell_exec($command);
    // Output
    $output .= "# {$command}\n";
    $output .= $tmp."\n";
}

echo $output;
