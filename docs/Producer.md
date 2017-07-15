# Producer
A producer returns an AMQPMessage with the data which is send via RabbitMQ.

## Adding custom producers with standard message
To create a producer, you also need to add a new model and adjust your config.xml. Per default all data in and object is returned. Per default a producer handles following applicable events "model_save_commit_after" and also "model_delete_commit_after". In case you need it you can also customize it. Simply add a property like in the collector example to produce a custom event message.

```xml
<config>
    <rabbitmq>
        <producers>
            <your_unique_producer_identifier>your_module/producer</your_unique_producer_identifier>
        </producers>
    </amqp>
</config>
```

```php
<?php

class Your_Module_Model_Producer
    extends FireGento_RabbitMQ_Applicable
{

    protected $routingKey = 'magento.yournamespace.yourentity.%s';

    protected $applicableClasses = array(
        'YourNameSpace_YourModule_Model_YourModel'
    );
}
```
## Adding custom producers with custom message
To customize the content of the message simply overload the method "getRabbitMQMessage" in your custom producer and add the content to the body content.

```php
<?php
/**
 * Navision Service Customer RabbitMQ Message Producer
 *
 */
class Service_Navision_Model_Producer_Customer
    extends FireGento_RabbitMQ_Producer
{

    /**
     * routing key / topic for message
     *
     * @var string
     */
    protected $routingKey = 'magento.navision.customer.%s';

    /**
     * class names where producer is applicable
     *
     * @var string[]
     */
    protected $applicableClasses = array(
        'Mage_Customer_Model_Customer'
    );

    /**
     * get message for current object
     *
     * @return PhpAmqpLib\Message\AMQPMessage[]
     */
    protected function getRabbitMQMessage()
    {
        $body = array();
        $body['data'] = $this->getObject()->getId();
        return $this->createRabbitmqMessage(
                sprintf($this->getRoutingKey(), $this->getEventType()), 
                $body
            );
    }
}
```