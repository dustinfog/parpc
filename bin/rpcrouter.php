<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/1/16
 * Time: 下午6:41
 *
 */
// set up the new autoloader using namespaces
require dirname(__DIR__) . "/bootstrap.php";

$router = new \Parpc\RemoteProcedureRouter();

$remoteAddrValidator = new \Parpc\RemoteAddrValidator(array("127.0.0.1"));
$router->addSecurityValidator($remoteAddrValidator);

$router->route();

/**
 * Class Dog
 */
class Dog
{
    /**
     * @return string
     * @throws Exception
     */
    public function getName()
    {
        return "bingo";
    }
}

/**
 * @return string
 */
function walk()
{
    return "walking 1";
}

