<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/1/23
 * Time: 下午2:45
 */

namespace Parpc;

/**
 * Class RemoteAddrValidator
 * @package Parpc
 */
class RemoteAddrValidator implements SecurityValidator{
    /**
     * @param string[] $allowedIps
     */
    public function __construct(array $allowedIps = null)
    {
        $this->allowedIps = $allowedIps;
    }

    /**
     * @var string[];
     */
    private $allowedIps;

    /**
     * @return \string[]
     */
    public function getAllowedIps()
    {
        return $this->allowedIps;
    }

    /**
     * @param string[] $ips
     */
    public function setAllowedIps($ips)
    {
        $this->allowedIps = $ips;
    }

    /**
     * @return string
     */
    public function getFailureMessage()
    {
        return "denied remote address";
    }


    /**
     * @param RemoteProcedure $procedure
     * @return bool
     */
    public function validate(RemoteProcedure $procedure)
    {
        return is_array($this->allowedIps) && isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], $this->allowedIps);
    }
}
