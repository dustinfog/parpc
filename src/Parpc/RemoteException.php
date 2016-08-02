<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/1/23
 * Time: 下午3:05
 */

namespace Parpc;

/**
 * Class RemoteException
 * @package Parpc
 */
class RemoteException extends \Exception
{
    /**
     * @param string $message
     * @param \Exception $cause
     */
    public function __construct($message, \Exception $cause = null)
    {
        parent::__construct($message, 0, $cause);
    }
}
