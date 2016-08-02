<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/1/23
 * Time: 下午2:42
 */

namespace Parpc;

/**
 * Interface SecurityValidator
 * @package Parpc
 */
interface SecurityValidator {

    /**
     * @return string
     */
    public function getFailureMessage();


    /**
     * @param RemoteProcedure $procedure
     * @return bool
     */
    public function validate(RemoteProcedure $procedure);
}
