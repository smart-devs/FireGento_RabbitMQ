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
 * applicable check utility
 *
 * @category FireGento
 * @package  FireGento_RabbitMQ
 * @author   FireGento Team <team@firegento.com>
 */
abstract class FireGento_RabbitMQ_Applicable
    implements FireGento_RabbitMQ_Interface_Applicable
{
    /**
     * List of applicable class names
     *
     * @var string[]
     */
    protected $applicableClasses = array();

    /**
     * List of applicable event names
     *
     * @var string[]
     */
    protected $applicableEvents = array(
        'model_save_commit_after',
        'model_delete_commit_after'
    );

    /**
     * Returns true if the event object is an instance of applicable classes
     *
     * @param Varien_Event $event
     * @return bool
     */
    public function isApplicable(Varien_Event $event)
    {
        foreach ($this->applicableClasses as $className) {
            if ($event->getObject() instanceof $className && in_array($event->getName(), $this->applicableEvents)) {
                return true;
            }
        }
        return false;
    }
}