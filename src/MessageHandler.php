<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace tumblrapi;

use Exception;

/**
 * Description of MessageHandler
 *
 * @author V
 */
interface MessageHandler {

    function handler($result);

    function onException(Exception $exception, int $page);
}
