<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/1/21
 * Time: 下午3:44
 */

namespace Parpc;

/**
 * Class RemoteObject
 * @package Parpc
 */
class RemoteObject
{
    /**
     * @param $className
     * @param array $constructArgs
     * @param RemoteContext $context
     */
    public function __construct($className, $constructArgs = array(), RemoteContext $context = null)
    {
        $this->context = $context;
        $this->className = $className;
        $this->constructArgs = $constructArgs;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->context . "#" . $this->className;
    }

    /**
     * @var RemoteContext
     */
    private $context;
    /**
     * @var string
     */
    private $className;
    /**
     * @var array
     */
    private $constructArgs;

    /**
     * @param $name
     * @param $arguments
     * @return RemoteProcedure|mixed
     * @throws RemoteException
     */
    public function __call($name, $arguments)
    {
        return $this->getContext()->exec(new RemoteProcedure($name, $arguments, $this->context, $this));
    }

    /**
     * @return RemoteContext
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return array
     */
    public function getConstructArgs()
    {
        return $this->constructArgs;
    }
}
