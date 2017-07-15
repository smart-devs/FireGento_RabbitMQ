# Collectors
A collector collects objects on model save and delete actions.
To create a collector, you need to add a new model and you need to adjust your config.xml.

## Adding custom collectors
Adding custom collectors for your own module / entity is simply adding a node in config.xml and creating a small collector model.


```xml
<config>
    <rabbitmq>
        <collectors>
            <your_unique_collector_identifier>your_module/collector</your_unique_identifier>
        </collectors>
    </amqp>
</config>
```

```php
<?php

class Your_Module_Model_Collector
    extends FireGento_RabbitMQ_Model_Abstract_Collector
    implements FireGento_RabbitMQ_Interface_Collector
{
    protected $applicableClasses = array(
        'Your_Module_Model_Entity'
    );
}
```
## Adding custom events
Per default the collector listens to following applicable events "model_save_commit_after" and also "model_delete_commit_after". In case you need it you can customize it. In the following example i want to collect the customer login event.

```xml
<config>
    <global>
        <events>
            <customer_login>
                <observers>
                    <rabbitmq_customer_login>
                        <type>singleton</type>
                        <class>rabbitmq/observer</class>
                        <method>collect</method>
                    </rabbitmq_customer_login>
                </observers>
            </customer_login>
        </collectors>
    </global>
</config>
```


```php
<?php

class Your_Module_Model_Collector
    extends FireGento_RabbitMQ_Model_Abstract_Collector
    implements FireGento_RabbitMQ_Interface_Collector
{
    protected $applicableClasses = array(
        'Mage_Customer_Model_Customer'
    );
    
    /**
     * List of applicable event names
     *
     * @var string[]
     */
    protected $applicableEvents = array(
        'customer_login'
    );
}
```