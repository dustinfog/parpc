<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/1/16
 * Time: 下午3:17
 */

namespace Parpc;

/**
 * Class RemoteProcedureCaller
 * @package Parpc
 */
class RemoteProcedureCaller
{
    private $timeout = .5;
    /**
     * @var RemoteProcedure[]
     */
    private $procedures = array();

    /**
     * @return float
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param float $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @param RemoteProcedure $procedure
     * @return RemoteProcedure
     */
    public function addProcedure(RemoteProcedure $procedure)
    {
        $this->procedures[] = $procedure;
        return $procedure;
    }

    /**
     * clear all procedure done it's work
     */
    public function resetProcedure()
    {
        $this->procedures = array();
    }

    /**
     * @param $routerURL
     * @return RemoteContext
     */
    public function createContext($routerURL)
    {
        return new RemoteContext($routerURL, $this);
    }

    /**
     * commit call request, and execute the callback on every procedure response
     * @return mixed
     */
    public function commit()
    {
        if (count($this->procedures) == 0)
            return null;

        $queue = curl_multi_init();

        $procedureMap = $this->prepare($queue);
        $ret = $this->exec($queue, $procedureMap);

        curl_multi_close($queue);

        return $ret;
    }

    /**
     * @param $queue
     * @return array
     */
    private function prepare($queue) {
        $procedureMap = array();

        foreach ($this->procedures as $procedure) {
            $ch = $procedure->createCurlHandler();
            curl_multi_add_handle($queue, $ch);
            $procedureMap[(string)$ch] = $procedure;
        }

        return $procedureMap;
    }

    /**
     * @param $queue
     * @param $procedureMap
     * @return null
     */
    private function exec($queue, $procedureMap) {
        $reduction = null;
        do {
            while (($code = curl_multi_exec($queue, $active)) == CURLM_CALL_MULTI_PERFORM) ;

            if ($code != CURLM_OK) break;

            while (($done = curl_multi_info_read($queue)) !== false) {
                $channel = $done['handle'];
                /** @var RemoteProcedure $procedure */
                $procedure = $procedureMap[(string)$channel];

                $content = null;
                if(($error = curl_error($channel)) != null)
                    $content = "Curl error: " . $error;
                else
                    $content = curl_multi_getcontent($channel);

                $procedure->receiveReturn(
                    $content,
                    $reduction
                );

                curl_multi_remove_handle($queue, $channel);
                curl_close($channel);
            }

            if ($active > 0) {
                curl_multi_select($queue, $this->getTimeout());
            }

        } while ($active);

        return $reduction;
    }
}
