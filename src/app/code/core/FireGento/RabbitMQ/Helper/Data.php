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
 * This helper acts as object cache for initialized consumers and helpers
 *
 * @category FireGento
 * @package  FireGento_RabbitMQ
 * @author   FireGento Team <team@firegento.com>
 */
class FireGento_RabbitMQ_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_NAME_ACTOR_CONSUMER = 'consumer';
    const XML_NAME_ACTOR_PUBLISHER = 'publisher';

    /**
     * xml path to publisher nodes
     */
    const XML_PATH_ACTOR_CONFIG = 'rabbitmq/%s/%s';

    /**
     * publisher instances
     *
     * @var FireGento_RabbitMQ_Model_Publisher[]
     */
    private $publisherInstances = array();

    /**
     * consumer instances
     *
     * @var FireGento_RabbitMQ_Model_Consumer[]
     */
    private $consumerInstances = array();

    /**
     * config instance
     *
     * @var FireGento_RabbitMQ_Model_Config
     */
    private $config = null;


    /**
     * get config instance
     *
     * @return FireGento_RabbitMQ_Model_Config
     */
    public function getConfig()
    {
        if (null === $this->config) {
            $this->config = Mage::getModel('rabbitmq/config');
        }
        return $this->config;
    }

    /**
     * get config for publisher
     *
     * @param string $name
     * @return Mage_Core_Model_Config_Element
     */
    private function getActorConfig($name = null, $actor = null)
    {
        switch ($actor) {
            case FireGento_RabbitMQ_Actor::ACTOR_TYPE_PUBLISHER: {
                $config = $this->getConfig()->getPublisher($name);
                if ($config instanceof Varien_Object) {
                    return $config;
                }
                throw new RuntimeException(sprintf('Missing configuration for publisher "%s"', $name));
                break;
            }
            case FireGento_RabbitMQ_Actor::ACTOR_TYPE_CONSUMER: {
                $config = $this->getConfig()->getConsumer($name);
                if ($config instanceof Varien_Object) {
                    return $config;
                }
                throw new RuntimeException(sprintf('Missing configuration for consumer "%s"', $name));
                break;
            }
            default: {
                throw new RuntimeException(sprintf('Unknown Actor "%s"', $actor));
            }
        }
    }

    /**
     * get publisher instance
     *
     * @param string $name
     * @return FireGento_RabbitMQ_Model_Publisher
     */
    public function getPublisher($name = null)
    {
        if (strlen($name) == 0 || true === $name || false === $name || null === $name) {
            throw new InvalidArgumentException('missing required name parameter for publisher');
        }
        if (false === isset($this->publisherInstances[$name])) {
            $this->publisherInstances[$name] = Mage::getModel('rabbitmq/publisher', $this->getActorConfig($name, FireGento_RabbitMQ_Actor::ACTOR_TYPE_PUBLISHER));
            $this->publisherInstances[$name]->setActorName($name);
        }
        return $this->publisherInstances[$name];
    }

    /**
     * get publisher instance
     *
     * @param string $name
     * @return FireGento_RabbitMQ_Model_Consumer
     */
    public function getConsumer($name = null)
    {
        if (strlen($name) == 0 || true === $name || false === $name || null === $name) {
            throw new InvalidArgumentException('missing required name parameter for consumer');
        }
        if (false === isset($this->consumerInstances[$name])) {
            $this->consumerInstances[$name] = Mage::getModel('rabbitmq/consumer', $this->getActorConfig($name, FireGento_RabbitMQ_Actor::ACTOR_TYPE_CONSUMER));
            $this->consumerInstances[$name]->setActorName($name);
        }
        return $this->consumerInstances[$name];
    }
}