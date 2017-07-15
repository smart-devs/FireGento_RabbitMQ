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

use PhpAmqpLib\Message\AMQPMessage;

/**
 * basic methods for producers
 *
 * @category FireGento
 * @package  FireGento_RabbitMQ
 * @author   FireGento Team <team@firegento.com>
 */
abstract class FireGento_RabbitMQ_Producer
    extends FireGento_RabbitMQ_Applicable
    implements FireGento_RabbitMQ_Interface_Producer
{
    /**
     * class names where producer is applicable
     *
     * @var string[]
     */
    protected $applicableClasses = array();

    /**
     * @var Varien_Event
     */
    protected $event = null;

    /**
     * the object
     *
     * @var Mage_Core_Model_Abstract
     */
    protected $object = null;

    /**
     * the event name
     *
     * @var string
     */
    protected $eventName = null;

    /**
     * message priority
     *
     * @var int
     */
    protected $priority = 0;

    /**
     * @return int
     */
    protected function getPriority()
    {
        return $this->priority;
    }

    /**
     * get routing key for message
     *
     * @return string
     */
    protected function getRoutingKey()
    {
        return $this->routingKey;
    }

    /**
     * set current event object
     *
     * @param Varien_Event $event
     * @return $this
     */
    protected function setEvent(Varien_Event $event)
    {
        $this->event = $event;
        return $this;
    }

    /**
     * get current event object
     *
     * @return Varien_Event
     */
    protected function getEvent()
    {
        return $this->event;
    }

    /**
     * set current object
     *
     * @param Mage_Core_Model_Abstract $object
     * @return $this
     */
    public function setObject(Mage_Core_Model_Abstract $object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * get current object
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function getObject()
    {
        return $this->object;
    }

    /**
     * get id from object
     *
     * @return integer
     */
    protected function getObjectId()
    {
        $object = $this->getobject();
        $id = $object->getId() > 0 ? $object->getId() : $object->getOrigData($object->getIdFieldName());
        if (!$id > 0) {
            throw new LogicException('Object Entity ID should be greater then zero');
        }
        return $id;
    }

    /**
     * checks if the object is new
     *
     * @return bool
     */
    protected function isObjectNew()
    {
        $object = $this->getobject();
        $id = $object->getData($object->getIdFieldName());
        $idOrg = $object->getOrigData($object->getIdFieldName());
        return null === $idOrg && $id > 0;
    }

    /**
     * set current event name
     *
     * @param string $object
     * @return $this
     */
    protected function setEventName($eventName)
    {
        $this->eventName = $eventName;
        return $this;
    }

    /**
     * get current event object
     *
     * @return string
     */
    protected function getEventName()
    {
        return $this->eventName;
    }

    /**
     * init current producer with event data
     *
     * @param Varien_Event $event
     * @return $this
     */
    protected function initFromEvent(Varien_Event $event)
    {
        $this->setEvent($event);
        $this->setEventName($event->getName());
        $this->setObject($event->getData('object'));

        return $this;
    }

    /**
     * return diff according to changed attributes
     *
     * @return array
     */
    protected function getChangedAttributes()
    {
        $object = $this->getobject();
        $diff = array();
        if (false === is_array($object->getOrigData())) {
            $attributes = array_keys($object->getData());
        } else {
            $attributes = array_keys($object->getOrigData());
        }
        foreach ($attributes as $attribute) {
            $orgData = $object->getOrigData($attribute);
            $data = $object->getData($attribute);
            switch (true) {
                case $attribute == 'created_at':
                case $attribute == 'updated_at':
                case true === is_object($data):
                case null === $data:
                case $data == $orgData: {
                    continue;
                    break;
                }
                default: {
                    $diff[$attribute] = $data;
                }
            }
        }
        return array_keys($diff);
    }

    /**
     * generate event type for topic
     *
     * @return bool|string
     */
    protected function getEventType()
    {
        switch (true) {
            case strpos($this->getEventName(), 'save') !== false && true === $this->isObjectNew($this->getobject()): {
                return 'create';
                break;
            }
            case strpos($this->getEventName(), 'save') !== false: {
                return 'update';
                break;
            }
            case strpos($this->getEventName(), 'delete') !== false: {
                return 'delete';
                break;
            }
            default: {
                return false;
            }
        }
    }

    /**
     * produce event message
     *
     * @param Varien_Event $event
     * @return AMQPMessage[]
     */
    public function produce(Varien_Event $event)
    {
        $this->initFromEvent($event);
        //check we have an valid event
        if ($this->getEventType() === false) {
            return array();
        }
        return $this->getRabbitMQMessage();
    }

    /**
     * @param string $routingKey routing key
     * @param array $body message body
     * @return AMQPMessage[]
     */
    protected function createRabbitmqMessage($routingKey, array $body)
    {
        return array($routingKey =>
            new AMQPMessage(
                json_encode($body, JSON_PRETTY_PRINT),
                array(
                    'content_type' => 'application/json',
                    'priority' => $this->getPriority(),
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
                )
            )
        );
    }

    /**
     * get message for current object
     *
     * @return AMQPMessage[]
     */
    protected function getRabbitMQMessage()
    {
        $body = array();
        $body['id'] = $this->getObjectId();
        $body['event'] = $this->getEventType();
        $body['payload']['changes'] = $this->getChangedAttributes();
        $body['payload']['data'] = array_filter($this->getObject()->getData(), function ($item) {
            switch (true) {
                case is_object($item): {
                    return false;
                }
            }
            return true;
        });
        if (is_array($this->getObject()->getOrigData())) {
            $body['payload']['orgdata'] = array_filter($this->getObject()->getOrigData(), function ($item) {
                switch (true) {
                    case is_object($item): {
                        return false;
                    }
                }
                return true;
            });
        }
        return $this->createRabbitmqMessage(sprintf($this->getRoutingKey(), $this->getEventType()), $body);
    }
}