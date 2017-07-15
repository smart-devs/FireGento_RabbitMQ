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
 * tries to find matching events to collect
 *
 * @category FireGento
 * @package  FireGento_RabbitMQ
 * @author   FireGento Team <team@firegento.com>
 */
class FireGento_RabbitMQ_Model_Collector extends FireGento_RabbitMQ_Facade
{
    /**
     * xml path to config node with collectors
     */
    const XML_PATH_COLLECTORS = 'rabbitmq/collectors';

    /**
     * xml path to config node with collectors
     *
     * @var string
     */
    protected $_itemsXmlPath = self::XML_PATH_COLLECTORS;

    /**
     * FireGento_RabbitMQ_Model_Collector constructor.
     */
    public function __construct()
    {
        $this->_requiredInterfaces[] = 'FireGento_RabbitMQ_Interface_Collector';
    }

    /**
     * Collects list of event objects
     *
     * @param Varien_Event $event
     * @return Varien_Event[]
     */
    public function collect(Varien_Event $event)
    {
        return $this->walk('collect', $event, $event);
    }

}
