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

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;

/**
 * generic methods for creating consumers and producers
 *
 * @category FireGento
 * @package  FireGento_RabbitMQ
 * @author   FireGento Team <team@firegento.com>
 */
abstract class FireGento_RabbitMQ_Actor
{
    const ACTOR_TYPE_PUBLISHER = 'publisher';
    const ACTOR_TYPE_CONSUMER = 'consumer';

    /**
     * path in config xml to broker connection node
     */
    const XML_PATH_CONNECTION_CONFIG = 'global/resources/rabbitmq/%s/connection';

    /**
     * path in config xml to broker connection node
     */
    const XML_PATH_ACTOR_CONNECTION_NAME = 'rabbitmq/%s/%s/*/connection';

    /**
     * name for the default connection name
     */
    const DEFAULT_CONNECTION_NAME = 'default';

    /**
     * @var Varien_Object
     */
    private $config = null;

    /**
     * current actor type
     *
     * @var string
     */
    private $actorType = null;

    /**
     * current actor name
     *
     * @var string
     */
    private $actorName = null;

    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    private $channel = null;

    /**
     * @var AMQPStreamConnection
     */
    private $connection = null;

    /**
     * current topic
     *
     * @var string
     */
    private $topic = null;

    /**
     * FireGento_RabbitMQ_Model_Broker constructor.
     *
     * @param Varien_Object $config
     * @param string $actorName name for current actor
     */
    public function __construct(Varien_Object $config)
    {
        $this->setConfig($config);
        // close rabbitmq connection and send batch messages from publisher
        register_shutdown_function(array($this, 'closeConnection'));
    }

    /**
     * get config object
     *
     * @return Varien_Object
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * @param Varien_Object $config
     * @return $this
     */
    protected function setConfig(Varien_Object $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return string
     */
    protected function getActorType()
    {
        return $this->actorType;
    }

    /**
     * @param string $actorType
     */
    protected function setActorType($actorType)
    {
        $this->actorType = $actorType;
        return $this;
    }

    /**
     * @return string
     */
    public function getActorName()
    {
        return $this->actorName;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @param string $topic
     */
    public function setTopic($topic)
    {
        $this->topic = (string)$topic;
    }

    /**
     * @param string $actorName
     */
    public function setActorName($actorName)
    {
        if (true === empty($actorName)) {
            throw new InvalidArgumentException('Parameter $actorName is empty.');
        }
        $this->actorName = $actorName;
        return $this;
    }

    /**
     * declare an exchange
     *
     * @param string $exchange
     * @param string $type
     * @param bool $passive
     * @param bool $durable
     * @param bool $auto_delete
     *
     * @return mixed
     */
    protected function declareExchange($exchange, $type = 'direct', $passive = false, $durable = false, $autoDelete = true)
    {
        return $this->getChannel()->exchange_declare($exchange, $type, $passive, $durable, $autoDelete);
    }

    /**
     * declare an queue
     *
     * @return mixed
     */
    protected function declareQueue($queue = '', $passive = false, $durable = false, $exclusive = false, $autoDelete = true)
    {
        return $this->getChannel()->queue_declare($queue, $passive, $durable, $exclusive, $autoDelete);
    }

    /**
     * bind topic to an queue
     *
     * @param string $queue
     * @param string $exchange
     * @param string $routing_key
     *
     * @return void
     */
    protected function bindTopic($queue, $exchange, $routing_key)
    {
        $this->getChannel()->queue_bind($queue, $exchange, $routing_key);
    }

    /**
     * get current connection config name
     *
     * @return Mage_Core_Model_Config_Element
     */
    private function getConnectionConfig()
    {
        //find connection name with fallback to default connection
        if (!$name = $this->getConfig()->getConnection()) {
            $name = self::DEFAULT_CONNECTION_NAME;
        }
        $config = Mage::getConfig()->getNode(sprintf(self::XML_PATH_CONNECTION_CONFIG, $name));
        if (!$config instanceof SimpleXMLElement || false === $config->hasChildren()) {
            throw new RuntimeException(sprintf('Missing "rabbitmq" connection config for "%s"', $name));
        }
        return $config;
    }

    /**
     * get rabbitmq connection
     *
     * @return AMQPStreamConnection
     * @todo add fallback to defaults
     * @throws \Exception
     */
    protected function getConnection()
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $config = $this->getConnectionConfig();
            try {
                $this->connection = new AMQPStreamConnection(
                    (string)$config->host,
                    (string)$config->port,
                    (string)$config->user,
                    (string)$config->password,
                    (string)$config->vhost
                );
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $this->connection;
    }

    /**
     * close the rabbitmq connection
     *
     * @return void
     *
     * @throws \Exception
     */
    public function closeConnection()
    {
        if (null !== $this->channel && $this->channel instanceof AMQPChannel) {
            $this->channel->close();
            $this->channel = null;
        }
        if ($this->connection !== null && $this->connection->isConnected()) {
            $this->connection->close();
        }
        $this->connection = null;
    }

    /**
     * get channel instance for current actor
     *
     * @return AMQPChannel
     */
    protected function getChannel()
    {
        if (null === $this->channel) {
            $this->channel = $this->getConnection()->channel();
            switch ($this->getActorType()) {
                case self::ACTOR_TYPE_PUBLISHER: {
                    $this->initPublisherConfig();
                    break;
                }
                case self::ACTOR_TYPE_CONSUMER: {
                    $this->initConsumerConfig();
                }
            }
        }
        return $this->channel;
    }
}