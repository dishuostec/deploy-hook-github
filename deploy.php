<?php
define('DOCROOT', realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR);

function debug($data, $clean = FALSE)
{
	if (is_array($data))
	{
		$data = var_export($data, TRUE);
	}

	file_put_contents(DOCROOT.'_debug', $data."\n", $clean ? NULL : FILE_APPEND);
}

debug('START');
debug(date('Y-m-d H:i:s'), TRUE);
$json = file_get_contents('php://input');
debug($json);

$expect_projects = [];
if (file_exists('env.php'))
{
	debug('load env');
	include_once 'env.php';
	debug('load env done');
}
defined('PROJECTS_DIR') || define('PROJECTS_DIR', '/data/website');
defined('REPOSITORY_DIR') || define('REPOSITORY_DIR', '/data/repos');
defined('EXPECT_BRANCH') || define('EXPECT_BRANCH', 'master');

debug('decode json');
$data = json_decode($json);
if (empty($data) || json_last_error() !== JSON_ERROR_NONE)
{
	debug('decode json error:'.json_last_error_msg());
	echo 'invalid json data:'.json_last_error_msg();
	exit;
}
debug('decode json done');

$project = $data->repository->name;
$ref     = $data->ref;
$branch  = substr($ref, 11);

debug([
	'project' => $project,
	'ref'     => $ref,
	'branch'  => $branch,
]);

debug('check branch');
if ($branch !== EXPECT_BRANCH || ! in_array($project, $expect_projects))
{
	debug('skip '.$branch.'@'.$project);
	echo 'skip '.$branch.'@'.$project;
	exit;
}
debug('check branch done');

define('GIT_DIR', REPOSITORY_DIR.'/'.$project.'.git');
define('GIT', 'git --git-dir '.GIT_DIR.' ');
define('WORK_DIR', PROJECTS_DIR.'/'.$project);

if ( ! is_dir(REPOSITORY_DIR))
{
	debug('mkdir:'.REPOSITORY_DIR);
	mkdir(REPOSITORY_DIR, 755, TRUE);
}

if ( ! is_dir(GIT_DIR))
{
	// clone
	$repo_url   = $data->repository->url;
	$commands[] = 'git clone --mirror '.$repo_url.' '.GIT_DIR;
}

$work_dir_exist = is_dir(WORK_DIR);
if ( ! $work_dir_exist)
{
	debug('mkdir:'.WORK_DIR);
	mkdir(WORK_DIR, 755, TRUE);
}

chdir(WORK_DIR);
debug('chdir:'.WORK_DIR);

$commands[] = 'echo $PWD';
//$commands[] = 'whoami';
$commands[] = GIT.'fetch origin '.$ref.':'.$branch;
$commands[] = GIT.'show --summary --pretty=oneline '.$branch;

if ($work_dir_exist)
{
	// delete file
	foreach ($data->commits as $commit)
	{
		$commands[] = sprintf('%s diff-tree --name-only --no-commit-id --diff-filter=D -r %s | xargs rm -f',
			GIT, $commit->id);
	}
}

$commands[] = GIT.'archive '.$branch.' | tar -x -m -C '.WORK_DIR;

if (empty($cmd_after[$project][$branch]))
{
	$commands[] = '# No addtional commands';
}
else
{
	$commands[] = $cmd_after[$project][$branch];
}

$output = '';
foreach ($commands AS $command)
{
	// Run it
	$tmp = shell_exec($command);
	debug('exec:'.$command);
	debug('ret:'.$tmp);
	// Output
	$output .= "# {$command}\n";
	$output .= $tmp."\n";
}

debug('write log');
file_put_contents(DOCROOT.'log', $output, LOCK_EX);
echo $output;
debug('END');
