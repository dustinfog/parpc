<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/1/16
 * Time: 下午3:40
 */

namespace Parpc;

/**
 * Class RemoteProcedureRouter
 * @package Parpc
 */
class RemoteProcedureRouter
{
    /**
     * @var SecurityValidator[]
     */
    private $securityValidators = array();
    private $instances = array();

    /**
     * @param SecurityValidator $validator
     */
    public function addSecurityValidator(SecurityValidator $validator)
    {
        $this->securityValidators[] = $validator;
    }

    public function registerInstance($instance, $className = null)
    {
        if ($className == null) {
            $className = get_class($instance);
        }

        if (empty($className) || !($instance instanceof $className)) {
            throw new RemoteException('invalid instance');
        }

        $this->instances[$className] = $instance;
    }

    public function route()
    {
        ob_start();

        set_exception_handler(array($this, 'exceptionHandler'));

        $remoteProcedure = new RemoteProcedure();
        $remoteProcedure->unserialize(file_get_contents("php://input"));

        $exception = $this->validateSecurity($remoteProcedure);
        $ret = null;
        if (!$exception) {
            $procedure = null;

            try {
                $procedure = $this->getLocalProcedure($remoteProcedure);
                $ret = call_user_func_array($procedure, $remoteProcedure->getParams());
            } catch (\Exception $e) {
                $exception = new RemoteException($e->getMessage(), $e);
            }
        }

        $output = ob_get_clean();
        $serializeResponse = null;
        if (!$exception) {
            $serializeResponse = serialize(new RemoteResponse($ret, true, $output));
        } else {
            $serializeResponse = serialize(new RemoteResponse($exception, false, $output));
        }

        echo $serializeResponse;
    }

    public function exceptionHandler($exception) {
        echo serialize(new RemoteResponse($exception, false, ''));
    }

    /**
     * @param RemoteProcedure $procedure
     * @return RemoteException
     */
    private function validateSecurity(RemoteProcedure $procedure)
    {
        foreach ($this->securityValidators as $validator) {
            if (!$validator->validate($procedure)) {
                return new RemoteException("Security error: " . $validator->getFailureMessage());
            }
        }

        return null;
    }

    /**
     * @param RemoteProcedure $remoteProcedure
     * @return callable
     */
    private function getLocalProcedure(RemoteProcedure $remoteProcedure)
    {
        $procedureName = $remoteProcedure->getName();
        $remoteObject = $remoteProcedure->getObject();

        if ($remoteObject != null) {
            $className = $remoteObject->getClassName();

            if (!empty($this->instances[$className])) {
                $localObject = $this->instances[$className];
            } else {
                $class = new \ReflectionClass($className);
                $localObject = $class->newInstanceArgs($remoteObject->getConstructArgs());
            }

            return array($localObject, $procedureName);
        }

        return $procedureName;
    }
}
