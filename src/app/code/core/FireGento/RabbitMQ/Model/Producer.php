<?php
/**
 * This file is part of a FireGento e.V. module.
 *
 * This FireGento e.V. module is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 3 as
 * published by the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * PHP version 5
 *
 * @category  FireGento
 * @package   FireGento_RabbitMQ
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2017 FireGento Team (http://www.firegento.com)
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */

/**
 * tries to find matching events to produce
 *
 * @category FireGento
 * @package  FireGento_RabbitMQ
 * @author   FireGento Team <team@firegento.com>
 */
class FireGento_RabbitMQ_Model_Producer extends FireGento_RabbitMQ_Facade
{
    const XML_PATH_PRODUCERS = 'rabbitmq/producers';

    protected $_itemsXmlPath = self::XML_PATH_PRODUCERS;

    public function __construct()
    {
        $this->_requiredInterfaces[] = 'FireGento_RabbitMQ_Interface_Producer';
    }

    /**
     * Retrieves list of rabbitmq Messages for specified event object
     *
     * @param Varien_Event $event
     * @return \PhpAmqpLib\Message\AMQPMessage[]
     */
    public function getMessages(Varien_Event $event)
    {
        return $this->walk('produce', $event, $event);
    }
}