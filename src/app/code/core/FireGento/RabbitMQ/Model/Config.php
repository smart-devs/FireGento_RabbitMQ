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
 * provides information from all possible config files
 *
 * @category FireGento
 * @package  FireGento_RabbitMQ
 * @author   FireGento Team <team@firegento.com>
 */
class FireGento_RabbitMQ_Model_Config
{
    const CONFIG_MODEL = 'rabbitmq/config_%s';
    const CONFIG_CACHE_TAG = 'RABBITMQ';

    const FILE_NAME = 'rabbitmq.xml';

    /**
     * Flag which allow use cache logic
     *
     * @var bool
     */
    protected $_useCache = false;

    /**
     * @var FireGento_RabbitMQ_Model_Consumer[]
     */
    protected $consumers = array();

    /**
     * @var FireGento_RabbitMQ_Model_Publisher[]
     */
    protected $publishers = array();

    /**
     * Enter description here...
     *
     * @param string $id
     * @return FireGento_RabbitMQ_Model_Config
     */
    public function setCacheId($id)
    {
        $this->_cacheId = $id;
        return $this;
    }

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getCacheId()
    {
        return $this->_cacheId;
    }

    /**
     * Enter description here...
     *
     * @var string
     */
    protected $_cacheId = null;


    /**
     * FireGento_RabbitMQ_Model_Config internal constructor
     */
    public function __construct()
    {
        $this->setCacheId('config_rabbitmq');
        if (false === $this->initFromCache()) {
            $this->consumers = new Varien_Data_Collection();
            $this->publishers = new Varien_Data_Collection();
            $this->initFromFiles();
            $this->saveCache();
        }
    }

    /**
     * Load cached modules configuration
     *
     * @return bool
     */
    public function initFromCache()
    {
        if (false === Mage::app()->useCache('rabbitmq')) {
            return false;
        }
        if (Mage::isInstalled(array('etc_dir' => Mage::getConfig()->getOptions()->getEtcDir()))) {
            $data = Mage::app()->getCache()->load($this->getCacheId());
            if ($data && $content = @unserialize($data)) {
                if (false === is_array($content)) {
                    return false;
                }
                if (false === array_key_exists('consumers', $content) || false === array_key_exists('publishers', $content)) {
                    return false;
                }
                $this->consumers = $content['consumers'];
                $this->publishers = $content['publishers'];
                return true;
            }
        }
        return false;
    }

    /**
     * Iterate all active modules "etc" folders and combine data from
     * specified xml file name to one object
     *
     * @return  FireGento_RabbitMQ_Model_Config
     */
    public function initFromFiles()
    {
        foreach (Mage::getConfig()->getNode('modules')->children() as $name => $config) {
            if ($config->is('active')) {
                $configFile = Mage::getConfig()->getModuleDir('etc', $name) . DS . self::FILE_NAME;
                if (false === file_exists($configFile) || false === is_readable($configFile)) {
                    continue;
                }
                if ($xml = @simplexml_load_file($configFile)) {
                    $this->generatePublisherConfig($xml);
                    $this->generateConsumerConfig($xml);
                }
            }
        }

        return $this;
    }

    /**
     * save configuration in cache
     *
     * @return FireGento_RabbitMQ_Model_Config
     */
    private function saveCache()
    {
        if (false === Mage::app()->useCache('rabbitmq')) {
            return $this;
        }
        $tags = array(self::CONFIG_CACHE_TAG);
        Mage::app()->getCache()->save(
            serialize(array('consumers' => $this->consumers, 'publishers' => $this->publishers)), $this->getCacheId(), $tags, false);
    }

    /**
     * parse all the different rabbitmq.xml files in different modules
     *
     * @param SimpleXMLElement $xml
     * @return FireGento_RabbitMQ_Model_Config
     */
    private function generatePublisherConfig(SimpleXMLElement $xml)
    {
        foreach ($xml->xpath('/rabbitmq/publisher/*') as $publisherConfig) {
            //lookup publisher config or generate new one
            if (false === $publisher = $this->getPublisher($publisherConfig->getName())) {
                $publisher = new Varien_Object(array('name' => $publisherConfig->getName()));
                $publisher->setIdFieldName('name');
            }
            // loop over boolean attributes
            foreach ($publisherConfig->attributes() as $name => $value) {
                $publisher->setData($name, filter_var($value, FILTER_VALIDATE_BOOLEAN));
            }
            // loop over nodes
            foreach ($publisherConfig->children() as $name => $value) {
                //@attributes is in children so skip it
                if ($name == '@attributes' || $name == 'topics') {
                    continue;
                }
                $publisher->setData($name, (string)$value);
            }
            $this->setPublisher($publisher);
        }
        return $this;
    }

    /**
     * parse all the different rabbitmq.xml files in different modules
     *
     * @param SimpleXMLElement $xml
     * @return FireGento_RabbitMQ_Model_Config
     */
    private function generateConsumerConfig(SimpleXMLElement $xml)
    {
        foreach ($xml->xpath('/rabbitmq/consumer/*') as $consumerConfig) {
            //lookup consumer config or generate new one
            if (false === $consumer = $this->getConsumer($consumerConfig->getName())) {
                $consumer = new Varien_Object(array('name' => $consumerConfig->getName(), 'topics' => array()));
                $consumer->setIdFieldName('name');
            }
            // loop over boolean attributes
            foreach ($consumerConfig->attributes() as $name => $value) {
                $consumer->setData($name, filter_var($value, FILTER_VALIDATE_BOOLEAN));
            }
            // loop over nodes
            foreach ($consumerConfig->children() as $name => $value) {
                //@attributes is in children so skip it
                if ($name == '@attributes' || $name == 'topics') {
                    continue;
                }
                $consumer->setData($name, (string)$value);
            }
            //loop over topics
            foreach ($consumerConfig = $consumerConfig->topics->bind as $topic) {
                $topics = $consumer->getData('topics');
                $array_key = (string)spl_object_hash($topic);
                $topics[$array_key] = array(
                    'exchange' => (string)$topic->attributes()->exchange,
                    'topic' => (string)$topic->attributes()->topic);
                $consumer->setData('topics', $topics);
            }
            $this->setConsumer($consumer);
        }
        return $this;
    }

    /**
     * get consumer collection
     *
     * @return Varien_Data_Collection
     */
    public function getConsumerCollection()
    {
        return $this->consumers;
    }

    /**
     * get producer collection
     *
     * @return Varien_Data_Collection
     */
    public function getPublisherCollection()
    {
        return $this->publishers;
    }

    /**
     * checks a consumer exists
     *
     * @param string $name
     * @return bool
     */
    public function hasConsumer($name)
    {
        $item = $this->getConsumerCollection()->getItemById($name);
        return $item !== null && $item instanceof Varien_Object;
    }

    /**
     * get consumer by its name
     *
     * @param $name
     * @return bool|Varien_Object
     */
    public function getConsumer($name)
    {
        $item = $this->getConsumerCollection()->getItemById($name);
        return $item !== null && $item instanceof Varien_Object ? $item : false;
    }

    /**
     * set consumer
     *
     * @param Varien_Object
     * @return FireGento_RabbitMQ_Model_Config
     */
    public function setConsumer(Varien_Object $object)
    {
        $name = $object->getName();
        $this->getConsumerCollection()->removeItemByKey($name);
        $this->getConsumerCollection()->addItem($object);
        return $this;
    }

    /**
     * checks a producer exists
     *
     * @param string $name
     * @return bool
     */
    public function hasPublisher($name)
    {
        $item = $this->getPublisherCollection()->getItemById($name);
        return $item !== null && $item instanceof Varien_Object;
    }

    /**
     * get producer by its name
     *
     * @param $name
     * @return bool|Varien_Object
     */
    public function getPublisher($name)
    {
        $item = $this->getPublisherCollection()->getItemById($name);
        return $item !== null && $item instanceof Varien_Object ? $item : false;
    }

    /**
     * set publisher
     *
     * @param Varien_Object
     * @return FireGento_RabbitMQ_Model_Config
     */
    public function setPublisher(Varien_Object $object)
    {
        $name = $object->getName();
        $this->getPublisherCollection()->removeItemByKey($name);
        $this->getPublisherCollection()->addItem($object);
        return $this;
    }
}