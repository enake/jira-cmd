#!/usr/bin/env php
<?php

use GetOpt\GetOpt;
use GetOpt\Option;
use GetOpt\ArgumentException;
use GetOpt\ArgumentException\Missing;

define('NAME', 'JiraCMD');
define('VERSION', '0.1-alpha');

require __DIR__ . '/vendor/autoload.php';

$dotEnv = Dotenv\Dotenv::create(__DIR__);
$dotEnv->load();

$getOpt = new GetOpt();
// define common options
$getOpt->addOptions([
    Option::create(null, 'version', GetOpt::NO_ARGUMENT)
        ->setDescription('Show version information and quit'),

    Option::create('?', 'help', GetOpt::NO_ARGUMENT)
        ->setDescription('Show this help and quit'),

    Option::create(null,'key', GetOpt::REQUIRED_ARGUMENT)
        ->setDescription('JIRA task ID (DEMO-1)')
        ->setArgumentName('jira-id')
        ->setValidation(function ($value) {
            return preg_match('/((([A-Z]{1,10})-?)[A-Z]+-\d+)/',
                $value);
        }, 'This is not a valid JIRA ID'),

    Option::create('m','message', GetOpt::REQUIRED_ARGUMENT)
        ->setDescription('Comment that will be added to the task')
        ->setArgumentName('string'),
]);

try {
    try {
        $getOpt->process();
    } catch (Missing $exception) {
        // catch missing exceptions if help is requested
        if (!$getOpt->getOption('help')) {
            throw $exception;
        }
    }
} catch (ArgumentException $exception) {
    file_put_contents('php://stderr', $exception->getMessage() . PHP_EOL);
    //echo PHP_EOL . $getOpt->getHelpText();
    exit;
}

// show help and quit
if ($getOpt->getOption('help')) {
    echo $getOpt->getHelpText();
    exit;
}

// show version and quit
if ($getOpt->getOption('version')) {
    echo sprintf('%s: %s' . PHP_EOL, NAME, VERSION);
    exit;
}

// get Jira key
if ($getOpt->getOption('key')) {
    $key = $getOpt->getOption('key');
}

// get Jira comment
if ($getOpt->getOption('message')) {
    $message = $getOpt->getOption('message');
} else {
    $message = '';
}

/**
 * @return chobie\Jira\Api
 */
function getApiClient()
{
	$api = new \chobie\Jira\Api(
        getenv('ENDPOINT'),
		new \chobie\Jira\Api\Authentication\Basic(getenv('USERID'), getenv('PASSWORD'))
	);
	return $api;
}

$api = getApiClient();

//var_dump($api->getIssue($key));
//exit;

$jsonStringUpdate = '
{
    "comment": [
        {
            "add": {
                "body": "'.$message.'"
            }
        }
    ]
}';

$jsonStringTransition = '
{
    "id": "741"
}';

$r = $api->transition($key, array(
    'update' => json_decode($jsonStringUpdate),
    'transition' => json_decode($jsonStringTransition)
));

var_dump($r);

$jsonStringUpdate = '
{  
    "assignee":[  
        {  
            "set":{  
                "name":"' . getenv('ASSIGNEE') . '"
            }
        }
    ]
}';

$r = $api->editIssue($key, array(
    'update' => json_decode($jsonStringUpdate),
));

var_dump($r);

//var_dump($task->result->description);