<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/1/23
 * Time: 下午5:53
 */

namespace Parpc;

/**
 * Class RemoteResponse
 * @package Parpc
 */
class RemoteResponse
{
    /**
     * @param mixed $data
     * @param bool $successful
     */
    public function __construct($data, $successful, $output)
    {
        $this->successful = $successful;
        $this->data = $data;
    }

    /**
     * @var bool
     */
    private $successful;
    /**
     * @var mixed
     */
    private $data;

    /**
     * @var string
     */
    private $output;

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->successful;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

}
