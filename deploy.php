<?php
if (file_exists('env.php')) {
    include_once 'env.php';
}
defined('PROJECTS_DIR') || define('PROJECTS_DIR', '/data/website');

$data = json_decode(file_get_contents('php://input'));
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'invalid json data';
    exit;
}

$project = $data->repository->name;
$ref = $data->ref;
$branch = substr($ref, 11);

if (empty($expect_branch[$project][$branch])) {
	echo 'skip';
	exit;
}

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
    GIT.'fetch origin '.$ref.':'.$branch,
    GIT.'show --summary --pretty=oneline '.$branch,
);

if ($work_dir_exist) {
    // delete file
    foreach ($data->commits as $commit) {
        $commands[] = sprintf('%s diff-tree --name-only --no-commit-id --diff-filter=D -r %s | xargs rm -f',
            GIT, $commit->id);
    }
}

$commands[] = GIT.'archive '.$branch.' | tar -x -C '.WORK_DIR;

if (empty($cmd_after[$project][$branch])) {
    $commands[] = '# No addtional commands';
} else {
    $commands[] = $cmd_after[$project][$branch];
}

$output = '';
foreach ($commands AS $command) {
    // Run it
    $tmp = shell_exec($command);
    // Output
    $output .= "# {$command}\n";
    $output .= $tmp."\n";
}

echo $output;
