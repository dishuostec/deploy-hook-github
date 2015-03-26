<?php
$expect_projects = [];
if (file_exists('env.php'))
{
	include_once 'env.php';
}
defined('PROJECTS_DIR') || define('PROJECTS_DIR', '/data/website');
defined('REPOSITORY_DIR') || define('REPOSITORY_DIR', '/data/repos');
defined('EXPECT_BRANCH') || define('EXPECT_BRANCH', 'master');

$data = json_decode(file_get_contents('php://input'));
if (empty($data) || json_last_error() !== JSON_ERROR_NONE)
{
	echo 'invalid json data:'.json_last_error_msg();
	exit;
}

$project = $data->repository->name;
$ref     = $data->ref;
$branch  = substr($ref, 11);

if ($branch !== EXPECT_BRANCH || ! in_array($project, $expect_projects))
{
	echo 'skip';
	exit;
}

define('GIT_DIR', REPOSITORY_DIR.'/'.$project.'.git');
define('GIT', 'git --git-dir '.GIT_DIR.' ');
define('WORK_DIR', PROJECTS_DIR.'/'.$project);

if ( ! is_dir(REPOSITORY_DIR))
{
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
	mkdir(WORK_DIR, 755, TRUE);
}

chdir(WORK_DIR);

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

$commands[] = GIT.'archive '.$branch.' | tar -x -C '.WORK_DIR;

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
	// Output
	$output .= "# {$command}\n";
	$output .= $tmp."\n";
}

file_put_contents(realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'log', $output, LOCK_EX);
echo $output;
