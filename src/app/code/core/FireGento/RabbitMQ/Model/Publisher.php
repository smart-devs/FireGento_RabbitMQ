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
 * publishes messages to an defined exchange
 *
 * @category FireGento
 * @package  FireGento_RabbitMQ
 * @author   FireGento Team <team@firegento.com>
 */
use \PhpAmqpLib\Message\AMQPMessage;

class FireGento_RabbitMQ_Model_Publisher extends FireGento_RabbitMQ_Actor
{

    /**
     * FireGento_RabbitMQ_Model_Publisher constructor.
     *
     * @param Config $config
     */
    public function __construct(Varien_Object $config)
    {
        $this->setActorType(parent::ACTOR_TYPE_PUBLISHER);
        parent::__construct($config);
    }

    /**
     * init current publisher configuration
     *
     * @return void
     */
    protected function initPublisherConfig()
    {
        $config = $this->getConfig();
        $this->declareExchange($config->getTopic(), $config->getType(), $config->getPassive(), $config->getDurable(), $config->getAutoDelete());
    }

    /**
     * publish message in batch mode
     *
     * @param AMQPMessage $message
     * @param string $routingKey
     */
    public function batchPublish(AMQPMessage $message, $routingKey)
    {
        return $this->getChannel()->batch_basic_publish($message, $this->getConfig()->getTopic(), $routingKey);
    }

    /**
     * send all batch messages
     *
     * @return void
     */
    public function batchSend()
    {
        $this->getChannel()->publish_batch();
    }

    /**
     * publish message directly
     *
     * @param AMQPMessage $message
     * @param string $routingKey
     *
     * @return void
     */
    public function publish(AMQPMessage $message, $routingKey)
    {
        $this->getChannel()->basic_publish($message, $this->getConfig()->getTopic(), $routingKey);
    }
}