FireGento_RabbitMQ
======================
RabbitMQ implementation for Magento 1 with logic for producer and consumer.

Facts
-----
* version: 1.0.0
* [extension on GitHub](https://github.com/firegento/firegento-rabbitmq) 

Description
-----------
This module provides the possibility to publish and consume messages via rabbitmq. You can define multiple queues and exchanges via rabbitmq.xml

Requirements
-------------------
* PHP 5.3 or higher
* [php-amqplib](https://github.com/php-amqplib/php-amqplib) 

Compatibility
--------------
* Magento CE1.6.x-1.9.x/EE1.11.x-1.14.x

Installation Instructions
-------------------------
1. Install the extension via composer or modman. If you use modman please install [php-amqplib](https://github.com/php-amqplib/php-amqplib)  on your own.
2. Clear the cache, logout from the admin panel and then login again.
3. Configure and activate the extension under System - Configuration - FireGento - RabbitMQ
4. set up connection credentials in local.xml

```xml
<config>
    <global>
        <resources>
            <rabbitmq>
                <default>
                    <connection>
                        <host>127.0.0.1</host>
                        <port>5672</port>
                        <user>vagrant</user>
                        <password>vagrant</password>
                        <vhost>firegento_develop</vhost>
                    </connection>
                </default>
            </rabbitmq>
        </resources>
    </global>
</config>
```
Uninstallation
--------------
1. Remove all extension files from your Magento installation

Support
-------
If you have any issues with this extension, open an issue on [GitHub](https://github.com/firegento/FireGento_RabbitMQ/issues).

Contribution
------------
Any contribution is highly appreciated. The best way to contribute code is to open a [pull request on GitHub](https://help.github.com/articles/using-pull-requests).

Developer
---------
FireGento Team
* Website: [http://firegento.com](http://firegento.com)
* Twitter: [@firegento](https://twitter.com/firegento)

Team:
* [Daniel Niedergesäß (Lead)](https://twitter.com/sqlexception)
* [Andreas Mautz (Maintainer)](https://twitter.com/mautz_et_tong)

License
-------
[GNU General Public License, version 3 (GPLv3)](http://opensource.org/licenses/gpl-3.0)

Copyright
---------
(c) 2011-2017 FireGento Team