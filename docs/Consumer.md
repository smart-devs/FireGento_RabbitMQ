# Consumer
A consumer retrieves an AMQPMessage and dispatches an events with the data which is send via RabbitMQ.

## Adding custom consumer
To create a consumer, you need to create an rabbitmq.xml in the etc folder of your module. In the following example so see the implementation of an consumer with a name Navision.


```xml
<rabbitmq>
    <consumer>
        <navision ack="true" passive="false" durable="false" exclusive="false" auto_delete="false">
            <topics>
                <bind exchange="magento.producer" topic="magento.navision.customer.*"/>
                <bind exchange="magento.producer" topic="magento.navision.customer.address.*"/>
            </topics>
        </navision>
    </consumer>
</rabbitmq>
```

As example the topic "magento.navision.customer.create" would dispatch an event "rabbitmq_magento_navision_customer_create". To get the events dispatched, you need to create an event area inside of the config.xml of your module.
```xml
<config>
    <navision>
        <events>
            <rabbitmq_magento_navision_customer_create>
                <observers>
                    <!-- your observer logic -->
                </observers>
            </rabbitmq_magento_navision_customer_create>
    </navision>
</config>
```


Finally you can start your consumer `php shell/consumer.php --consume navision`

Note
----
If you wanna prevent an infinite loop during an model action in an consumer you can disable the collection / producing of events and messages in your consumer ```Mage::getSingleton('rabbitmq/observer')->disableEventCollectors()```. If you wanna reenable them ```Mage::getSingleton('rabbitmq/observer')->enableEventCollectors()```