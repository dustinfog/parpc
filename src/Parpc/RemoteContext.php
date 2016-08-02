<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/1/22
 * Time: 下午1:44
 */

namespace Parpc;

/**
 * Class RemoteContext
 * @package Parpc
 */
class RemoteContext
{
    /**
     * @var string
     */
    private $routerURL;
    /**
     * @var RemoteProcedureCaller
     */
    private $caller;
    private $shareCookies = false;

    /**
     * @param $routerURL
     * @param RemoteProcedureCaller $caller
     */
    public function __construct($routerURL, RemoteProcedureCaller $caller = null)
    {
        $this->setRouterURL($routerURL);
        $this->caller = $caller;
    }

    /**
     * @param $name
     * @param $arguments
     * @return RemoteProcedure|mixed
     * @throws RemoteException
     */
    public function __call($name, $arguments)
    {
        return $this->exec(new RemoteProcedure($name, $arguments, $this, null));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->routerURL;
    }

    /**
     * @param $className
     * @param array $constructArgs
     * @return RemoteObject
     */
    public function createObject($className, $constructArgs = array())
    {
        return new RemoteObject($className, $constructArgs, $this);
    }

    /**
     * @return RemoteProcedureCaller
     */
    public function getCaller()
    {
        return $this->caller;
    }

    /**
     * @return bool
     */
    public function isShareCookies()
    {
        return $this->shareCookies;
    }

    /**
     * @param bool $shareCookies
     */
    public function setShareCookies($shareCookies)
    {
        $this->shareCookies = $shareCookies;
    }

    /**
     * @return mixed
     */
    public function getRouterURL()
    {
        return $this->routerURL;
    }

    /**
     * @param $routerURL
     */
    public function setRouterURL($routerURL)
    {
        $this->routerURL = $routerURL;
    }

    /**
     * @param RemoteProcedure $procedure
     * @return RemoteProcedure|mixed
     * @throws RemoteException
     */
    public function exec(RemoteProcedure $procedure)
    {
        if ($this->caller != null) {
            return $this->caller->addProcedure($procedure);
        }

        $handler = $procedure->createCurlHandler();
        $content = curl_exec($handler);
        if (($error = curl_error($handler)) != null)
            $content = "Curl error: " . $error;

        $procedure->receiveReturn($content);

        $exception = $procedure->getException();
        if ($exception)
            throw $exception;

        return $procedure->getReturn();
    }
}
