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

    /**
     * @param SecurityValidator $validator
     */
    public function addSecurityValidator(SecurityValidator $validator)
    {
        $this->securityValidators[] = $validator;
    }

    public function route()
    {
        ob_start();

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
     * @return callback
     */
    private function getLocalProcedure(RemoteProcedure $remoteProcedure)
    {
        $procedureName = $remoteProcedure->getName();
        $remoteObject = $remoteProcedure->getObject();

        if ($remoteObject != null) {
            $class = new \ReflectionClass($remoteObject->getClassName());
            $localObject = $class->newInstanceArgs($remoteObject->getConstructArgs());
            return array($localObject, $procedureName);
        }

        return $procedureName;
    }
}
