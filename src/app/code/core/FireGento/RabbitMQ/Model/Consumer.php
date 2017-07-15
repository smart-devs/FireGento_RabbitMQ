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
 * consumes a queue transforms routing key to event and dispatch it
 *
 * @category FireGento
 * @package  FireGento_RabbitMQ
 * @author   FireGento Team <team@firegento.com>
 */

class FireGento_RabbitMQ_Model_Consumer extends FireGento_RabbitMQ_Actor
{
    /**
     * process is running
     *
     * @var bool
     */
    protected $running = true;

    /**
     * FireGento_RabbitMQ_Model_Consumer constructor.
     *
     * @param Varien_Object $config
     */
    public function __construct(Varien_Object $config)
    {
        $this->setActorType(parent::ACTOR_TYPE_CONSUMER);
        parent::__construct($config);
    }

    /**
     * Binds and topic routing key to an queue
     *
     * @param string $queue
     * @param string $exchange
     * @param string $topic
     */
    private function bindTopicToQueue($queue, $exchange, $topic)
    {
        $this->getChannel()->queue_bind($queue, $exchange, $topic);
    }

    /**
     * init current publisher configuration
     *
     * @return void
     */
    protected function initConsumerConfig()
    {
        $config = $this->getConfig();
        $status = $this->declareQueue($config->getName(), $config->getPassive(), $config->getDurable(), $config->getExclusive(), $config->getAutoDelete());
        if (false === is_array($config->getTopics())) {
            throw new RuntimeException(sprintf('Consumer "%s" needs topics to bind to', $config->getName()));
        }
        foreach ($config->getTopics() as $topic) {
            $status = $this->bindTopicToQueue($config->getName(), $topic['exchange'], $topic['topic']);
        }
    }

    /**
     * start the consumer
     *
     * @return void
     */
    public function start()
    {
        //rabbitmq_magento_catalog_category_delete
        $callback = function ($msg) {
            try {
                // get message data
                $body = json_decode($msg->body, true);
                // handle quit event
                if ($msg->body === 'quit') {
                    $msg->delivery_info['channel']->basic_cancel($msg->delivery_info['consumer_tag']);
                }
                // dispatch event
                Mage::dispatchEvent(sprintf('%s_%s', 'rabbitmq', str_replace('.', '_', $msg->delivery_info['routing_key'])), $body);
                // send ack if required
                if (true === $this->getConfig()->getAck()) {
                    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
            $msg = null;
            unset($msg);
        };
        $this->getChannel()->basic_consume($this->getActorName(), '', false, false === $this->getConfig()->getAck(), false, false, $callback);
        while (count($this->getChannel()->callbacks)) {
            $this->getChannel()->wait();
        }
    }
}