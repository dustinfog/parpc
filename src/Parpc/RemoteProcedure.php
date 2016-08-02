<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/1/16
 * Time: 下午3:34
 */

namespace Parpc;

/**
 * Class RemoteProcedure
 * @package Parpc
 */
class RemoteProcedure implements \Serializable
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var array
     */
    private $params;
    /**
     * @var RemoteObject;
     */
    private $object;
    /**
     * @var string
     */
    private $responseText;
    /**
     * @var bool
     */
    private $finished;
    /**
     * @var callback
     */
    private $successHandlers = array();
    /**
     * @var callback
     */
    private $failHandlers = array();
    /**
     * @var callback
     */
    private $completeHandlers = array();
    /**
     * @var RemoteContext
     */
    private $context;
    /**
     * @var RemoteException
     */
    private $exception;
    /**
     * @var mixed
     */
    private $return;

    private $timeout = .5;

    /**
     * @param string $name
     * @param array $params
     * @param RemoteContext $context
     * @param RemoteObject $object
     */
    public function __construct($name = null, array $params = null, RemoteContext $context = null, RemoteObject $object = null)
    {
        $this->name = $name;
        $this->params = $params;
        $this->context = $context;
        $this->object = $object;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return string
     */
    public function getResponseText()
    {
        return $this->responseText;
    }

    /**
     * @return RemoteContext
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return RemoteObject
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return RemoteException
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return mixed
     */
    public function getReturn()
    {
        return $this->return;
    }

    /**
     * @param callback $successHandler a function with args => $return, RemoteProcedure $procedure or &$reduction, invoked on calling success
     * @return RemoteProcedure
     */
    public function onSuccess($successHandler)
    {
        $this->successHandlers[] = $successHandler;
        return $this;
    }

    /**
     * @param callback $failHandler a function with args => Exception $e or RemoteProcedure $procedure, invoked on calling fail;
     * @return RemoteProcedure
     */
    public function onFail($failHandler)
    {
        $this->failHandlers[] = $failHandler;
        return $this;
    }

    /**
     * @param $completeHandler $completeHandler a function with args RemoteProcedure $procedure or empty, invoked after calling fail or success;
     * @return RemoteProcedure
     */
    public function onComplete($completeHandler)
    {
        $this->completeHandlers[] = $completeHandler;
        return $this;
    }

    /**
     * @param string $responseText
     * @param mixed $reduction
     */
    public function receiveReturn($responseText, &$reduction = null)
    {
        $this->responseText = $responseText;

        /** @var RemoteResponse $response */
        $response = unserialize($responseText);

        if ($response != null && $response instanceof RemoteResponse && $response->isSuccessful()) {
            $this->return = $response->getData();
            $this->invokeSuccessHandlers($this->return, $reduction);
        } else {
            if (!$response) {
                $this->exception = new RemoteException($responseText);
            } else if ($response instanceof RemoteResponse) {
                $this->exception = $response->getData();
            }

            $this->invokeFailHandlers($this->exception);
        }

        $this->invokeCompleteHandlers();
        if($response && $response->getOutput()) {
            echo PHP_EOL . $this . " output :" . PHP_EOL .$response->getOutput();
        }
        $this->finished = true;
    }

    /**
     * @param float $timeout
     */
    public function setTimeout($timeout)
    {
        $this->setTimeout($timeout);
    }

    /**
     * @return float
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param $return
     * @param $reduction
     */
    private function invokeSuccessHandlers($return, &$reduction)
    {
        $procedure = $this;

        $this->invokeHandlers($this->successHandlers, function (array $parameters) use ($procedure, $return, &$reduction) {
            $args = array();

            /** @var \ReflectionParameter $parameter */
            $parameter = null;
            foreach ($parameters as $parameter) {
                if ($parameter->isPassedByReference()) {
                    $args[] = &$reduction;
                } else if (($class = $parameter->getClass()) && $class->getName() == get_class($procedure)) {
                    $args[] = $procedure;
                } else {
                    $args[] = $return;
                }
            }

            return $args;
        });
    }

    /**
     * @param $handlers
     * @param $actualizeParams
     */
    private function invokeHandlers($handlers, $actualizeParams)
    {
        foreach ($handlers as $handler) {
            $method = null;
            $fun = null;
            if (is_array($handler)) {
                $method = new \ReflectionMethod($handler[0], $handler[1]);
                $method->setAccessible(true);

                $parameters = $method->getParameters();
            } else {
                $fun = new \ReflectionFunction($handler);
                $parameters = $fun->getParameters();
            }

            $args = $actualizeParams($parameters);

            if (isset($method)) {
                $method->invokeArgs($handler[0], $args);
            } else {
                $fun->invokeArgs($args);
            }
        }
    }

    /**
     * @param $exception
     */
    private function invokeFailHandlers($exception)
    {
        $procedure = $this;

        $this->invokeHandlers($this->failHandlers, function (array $parameters) use ($procedure, $exception) {
            $args = array();
            /** @var \ReflectionParameter $parameter */
            $parameter = null;
            foreach ($parameters as $parameter) {
                if (($class = $parameter->getClass()) && $class->getName() == get_class($procedure)) {
                    $args[] = $procedure;
                } else {
                    $args[] = $exception;
                }
            }

            return $args;
        });
    }

    private function invokeCompleteHandlers()
    {
        $procedure = $this;

        $this->invokeHandlers($this->completeHandlers, function (array $parameters) use ($procedure) {
            if (count($parameters) > 0)
                $args = array($procedure);
            else
                $args = array();
            return $args;
        });
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        return $this->finished;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        $array = array($this->name, $this->params);

        if ($this->object != null) {
            $array[] = $this->object->getClassName();
            $array[] = $this->object->getConstructArgs();
        }

        return serialize($array);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $raw = unserialize($serialized);

        $this->name = $raw[0];
        $this->params = $raw[1];

        $count = count($raw);
        if ($count > 2) {
            $className = $raw[2];
            if ($count > 3) {
                $constructArgs = $raw[3];
            } else {
                $constructArgs = null;
            }

            $this->object = new RemoteObject($className, $constructArgs);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $prefix = $this->object ? ($this->object . ".") : ($this->context . "#");
        return $prefix . $this->getName();
    }

    /**
     * @return resource
     */
    public function createCurlHandler()
    {
        $handler = curl_init();

        $context = $this->getContext();
        curl_setopt($handler, CURLOPT_URL, $context->getRouterURL());

        if ($context->isShareCookies() && array_key_exists('HTTP_COOKIE', $_SERVER))
            curl_setopt($handler, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);

        curl_setopt($handler, CURLOPT_POSTFIELDS, $this->serialize());
        curl_setopt($handler, CURLOPT_TIMEOUT, $this->getTimeout());
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handler, CURLOPT_HEADER, 0);
        curl_setopt($handler, CURLOPT_NOSIGNAL, true);
        curl_setopt($handler, CURLOPT_POST, 1);

        return $handler;
    }
}
