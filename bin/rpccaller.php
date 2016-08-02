<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/1/16
 * Time: 下午6:26
 */

// set up the new autoloader using namespaces
require dirname(__DIR__) . "/bootstrap.php";

$rpc = new \Parpc\RemoteProcedureCaller();

$context = $rpc->createContext("http://127.0.0.1:9000/");

$context->walk()->onSuccess(function($ret, &$test){
    echo "global function call : " . $ret . PHP_EOL;
    $test.= $ret;
})->onFail(function(\Parpc\RemoteException $e){
    echo $e->getMessage();
    echo "==========";
});

/** @var Dog $dog */
$dog = $context->createObject("Dog");
/** @var \Parpc\RemoteProcedure $procedure */
$procedure = $dog->getName();

$procedure->onSuccess(function($ret, &$test){
    echo "dog method call : " . $ret . PHP_EOL;
    $test.= $ret;
})->onFail(function(\Parpc\RemoteException $e){
    echo $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() .PHP_EOL;
})->onComplete(function(\Parpc\RemoteProcedure $procedure){
    echo $procedure;
});

echo $rpc->commit();
