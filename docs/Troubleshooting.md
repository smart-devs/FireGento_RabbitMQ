# Troubleshooting

* my consumer runs out of memory.
    - One of the problems is the Magento DB Profiler which collects stats for all queries.
        One solution is to disable the profiler in the local.xml
        ```xml
        <profiler>false</profiler>
        ```
    - Another problem is a possible memory leak caused by not proberly destructed objects. A solution would be to call the following method
        ```php 
        Mage_Core_Model_Abstract::clearInstance()
        ```
* my consumer saves a model and produces and infinite event loop
    - to prevent an infinite loop during an model action in an consumer you can disable the collection / producing of events and messages in your consumer 
        ```php
        Mage::getSingleton('rabbitmq/observer')->disableEventCollectors()
        ```
    - to enable them again you can use
        ```php
        Mage::getSingleton('rabbitmq/observer')->enableEventCollectors()
        ```