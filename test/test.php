<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
use Coroutine\Scheduler;
use Coroutine\SystemCall;

$GLOBALS['coids'] = [];

function taskSend($scheduler, $data)
{
    $t = microtime(true);
    $tid = $scheduler->getTaskId();
    $GLOBALS['coids'][$tid] = [
        'time'      =>  microtime(true),
        'timeout'   =>  $data,
    ];
    $scheduler->suspend();
    $result = yield;
    echo 'task-' . $data . '-result: ', $result, PHP_EOL, 'time: ', microtime(true) - $t, PHP_EOL;
}

function taskRecv($scheduler)
{
    while(true)
    {
        foreach($GLOBALS['coids'] as $id => $item)
        {
            if(microtime(true) - $item['time'] >= $item['timeout'] && false !== ($scheduler->resume($id, 'taskId: ' . $id)))
            {
                unset($GLOBALS['coids'][$id]);
            }
        }
        usleep(1000);
        yield;
    }
}

$scheduler = new Scheduler();

$scheduler->newTask(taskRecv($scheduler));
$scheduler->newTask(taskSend($scheduler, 1));
$scheduler->newTask(taskSend($scheduler, 2));
$scheduler->newTask(taskSend($scheduler, 4));

$scheduler->run();
