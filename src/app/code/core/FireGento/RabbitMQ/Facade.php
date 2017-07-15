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
 * abstract methods for collectors and processors
 *
 * @category FireGento
 * @package  FireGento_RabbitMQ
 * @author   FireGento Team <team@firegento.com>
 */
abstract class FireGento_RabbitMQ_Facade
{
    protected $_requiredInterfaces = array(
        'FireGento_RabbitMQ_Interface_Applicable'
    );

    /**
     * Path in the configuration to items config
     *
     * @var string
     */
    protected $_itemsXmlPath;

    /**
     * Items container
     *
     * @var FireGento_RabbitMQ_Interface_Applicable[]
     */
    protected $_items = array();

    /**
     * Adds an item to facade
     *
     * @param FireGento_RabbitMQ_Interface_Applicable $item
     * @return FireGento_RabbitMQ_Facade
     * @throws RuntimeException
     */
    public function add($item)
    {
        foreach ($this->_requiredInterfaces as $interface) {
            if (!$item instanceof $interface) {
                throw new RuntimeException(
                    sprintf('Item "%s" should implement "%s" interface', get_class($item), $interface)
                );
            }
        }

        $this->_items[spl_object_hash($item)] = $item;
        return $this;
    }

    /**
     * Removes items from facade
     *
     * @param FireGento_RabbitMQ_Interface_Applicable $item
     * @return FireGento_RabbitMQ_Facade
     */
    public function remove($item)
    {
        $hash = spl_object_hash($item);
        if (isset($this->_items[$hash])) {
            unset($this->_items[$hash]);
        }

        return $this;
    }

    /**
     * add items defined in config
     *
     * @param Mage_Core_Model_Config_Element $config
     */
    protected function addItemsFromConfig(Mage_Core_Model_Config_Element $config)
    {
        foreach ($config->children() as $node) {
            if (true === $node->hasChildren()) {
                $model = Mage::getModel((string)$node->class, $node->asArray());
            } else {
                $model = Mage::getModel($node);
            }
            $this->add($model);
        }
    }

    /**
     * Initializes default collector / processor items
     *
     * @return FireGento_RabbitMQ_Facade
     * @throws RuntimeException
     */
    protected function _initItems()
    {
        if (!$this->_itemsXmlPath) {
            throw new RuntimeException('XML Path for wrapper items is not specified');
        }
        /** @var Mage_Core_Model_Config_Element $config */
        $config = Mage::getConfig()->getNode($this->_itemsXmlPath);
        if (!$config instanceof Mage_Core_Model_Config_Element && false === $config->hasChildren()) {
            return $this;
        }
        $this->addItemsFromConfig($config);

        return $this;
    }

    /**
     * Retrieves facade items
     * If object is specified, it will filter out items by isApplicable interface
     *
     * @param null|object $object
     * @return FireGento_RabbitMQ_Interface_Applicable[]
     */
    public function items($object = null)
    {
        if (!$this->_items) {
            $this->_initItems();
        }
        $items = array();
        foreach ($this->_items as $item) {
            if ($object !== null && !$item->isApplicable($object)) {
                continue;
            }
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Invokes method on each facade item with specified arguments
     *
     * @param string $method
     * @param null|mixed $arg
     * @param null|object $object
     * @return array
     */
    public function walk($method, $arg = null, $object = null)
    {
        $result = array();
        foreach ($this->items($object) as $item) {
            $itemResult = $item->$method($arg);
            if ($itemResult === $item) {
                continue;
            }
            if (false === is_array($itemResult)) {
                $result[] = $itemResult;
            } elseif (isset($itemResult[0])) {
                array_splice($result, count($result), 0, $itemResult);
            } elseif (count($itemResult)) {
                $result += array_replace_recursive($result, $itemResult);
            }
        }

        return $result;
    }
}