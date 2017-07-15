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
 * class with callbacks for event dispatchers
 *
 * @category FireGento
 * @package  FireGento_RabbitMQ
 * @author   FireGento Team <team@firegento.com>
 */
class FireGento_RabbitMQ_Model_Observer
{

    /**
     * xml path to event sequence config for sorting events
     */
    const XML_PATH_EVENT_SEQUENCE = 'rabbitmq/sequence';

    /**
     * publisher name for magento events
     */
    const XML_PATH_DEFAULT_PUBLISHER_NAME = 'rabbitmq/default_publisher';

    /**
     * flag if event collecting is enabled / disabled
     *
     * @var bool
     */

    private $collectEvents = true;
    /**
     * event publisher instance
     *
     * @var FireGento_RabbitMQ_Model_Publisher
     */
    private $publisher = null;

    /**
     * array with collected evenrs
     *
     * @var Varien_Event[]
     */
    private $collectedEvents = array();

    /**
     * get publisher instance
     *
     * @return FireGento_RabbitMQ_Model_Publisher
     */
    private function getPublisher()
    {
        if (null === $this->publisher) {
            $this->publisher = Mage::helper('rabbitmq')->getPublisher((string)Mage::getConfig()->getNode(self::XML_PATH_DEFAULT_PUBLISHER_NAME));
        }
        return $this->publisher;
    }

    /**
     * enable collecting of events
     *
     * @return $this
     */
    public function enableEventCollectors()
    {
        $this->collectEvents = true;
        return $this;
    }

    /**
     * disable collecting of events
     *
     * @return $this
     */
    public function disableEventCollectors()
    {
        $this->collectEvents = false;
        return $this;
    }

    /**
     * event callback on save / update and delete events
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return $this
     */
    public function collect(Varien_Event_Observer $observer)
    {
        if (false === $this->collectEvents) {
            return $this;
        }
        if (false === is_object($observer->getEvent()->getObject())) {
            return $this;
        }
        $events = Mage::getSingleton('rabbitmq/collector')->collect($observer->getEvent());
        if (true === is_array($events) && count($events) > 0) {
            array_splice($this->collectedEvents, count($this->collectedEvents), 0, $events);
        }
        return $this;
    }

    /**
     * clean all collected events
     *
     * @return $this
     */
    public function clean()
    {
        $this->collectedEvents = array();
        return $this;
    }

    /**
     * publish all collected events to the queue
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function publish(Varien_Event_Observer $observer)
    {
        if (false === $this->collectEvents || count($this->collectedEvents) == 0) {
            return $this;
        }
        $batchCounter = 0;
        $sortedEvents = array();
        //resort events to their sequence
        foreach ($this->getSortedEventSequence() as $className) {
            $filteredEvents = array_filter($this->collectedEvents, function ($element) use ($className) {
                /** @var Varien_Event $element */
                return $element->getData('object') instanceof $className;
            }, ARRAY_FILTER_USE_BOTH);
            foreach ($filteredEvents as $offset => $event) {
                $sortedEvents[] = $event;
                unset($this->collectedEvents[$offset]);
            }
        }
        //append rest of the events
        foreach ($this->collectedEvents as $offset => $event) {
            $sortedEvents[] = $event;
            unset($this->collectedEvents[$offset]);
        }
        //publish sorted events
        foreach ($sortedEvents as $event) {
            /** @var Varien_Event $event */
            $rabbitmqMessages = Mage::getSingleton('rabbitmq/producer')->getMessages($event);
            foreach ($rabbitmqMessages as $routingKey => $rabbitmqMessage) {
                $this->getPublisher()->batchPublish($rabbitmqMessage, $routingKey);
                $batchCounter++;
            }
        }
        if ($batchCounter > 0) {
            $this->getPublisher()->batchSend();
        }
        return $this;
    }

    /**
     * get all events sorted
     *
     * @return array
     */
    protected function getSortedEventSequence()
    {
        $config = Mage::getConfig()->getNode(self::XML_PATH_EVENT_SEQUENCE);
        if (!$config instanceof Mage_Core_Model_Config_Element && false === $config->hasChildren()) {
            return $this->collectedEvents;
        }
        $configArray = array();
        foreach ($config->children() as $code => $data) {
            $configArray[$code] = $this->prepareConfigArray($data);
        }
        $sortedEvents = array_keys($configArray);
        // move all events with before specification in front of related event
        foreach ($configArray as $code => &$data) {
            foreach ($data['before'] as $positionCode) {
                if (!isset($configArray[$positionCode])) {
                    continue;
                }
                if (!in_array($code, $configArray[$positionCode]['after'], true)) {
                    // also add additional after condition for related event,
                    // to keep it always after event with before value specified
                    $configArray[$positionCode]['after'][] = $code;
                }
                $currentPosition = array_search($code, $sortedEvents, true);
                $desiredPosition = array_search($positionCode, $sortedEvents, true);
                if ($currentPosition > $desiredPosition) {
                    // only if current position is not corresponding to before condition
                    array_splice($sortedEvents, $currentPosition, 1); // removes existent
                    array_splice($sortedEvents, $desiredPosition, 0, $code); // add at new position
                }
            }
        }
        // Sort out event with after position specified
        foreach ($configArray as $code => &$data) {
            $maxAfter = null;
            $currentPosition = array_search($code, $sortedEvents, true);
            foreach ($data['after'] as $positionCode) {
                $maxAfter = max($maxAfter, array_search($positionCode, $sortedEvents, true));
            }
            if ($maxAfter !== null && $maxAfter > $currentPosition) {
                // move only if it is in front of after event
                array_splice($sortedEvents, $maxAfter + 1, 0, $code); // add at new position
                array_splice($sortedEvents, $currentPosition, 1); // removes existent
            }
        }
        $return = array();
        foreach ($sortedEvents as $event) {
            $return[$event] = $configArray[$event]['class'];
        }

        return $return;
    }


    /**
     * Prepare configuration array for sorting event models
     *
     * @param   string $code
     * @param   Mage_Core_Model_Config_Element $totalConfig
     * @return  array
     */
    private function prepareConfigArray($config)
    {
        $config = (array)$config;
        $return = array();
        if (isset($config['before'])) {
            $return['before'] = explode(',', $config['before']);
        } else {
            $return['before'] = array();
        }
        if (isset($config['after'])) {
            $return['after'] = explode(',', $config['after']);
        } else {
            $return['after'] = array();
        }
        $return['class'] = $config['class'];
        return $return;
    }
}
