<?php
require_once dirname($_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR . 'abstract.php';

/**
 * FireGento RabbitMQ Consumer Shell Script
 *
 * @category    FireGento
 * @package     FireGento_RabbitMQ
 */
class Mage_Shell_Consumer extends Mage_Shell_Abstract
{

    /**
     * @var FireGento_RabbitMQ_Model_Consumer
     */
    protected $consumer = null;

    /**
     * apply signal handling
     *
     * @return Mage_Shell_Consumer
     */
    protected function _construct()
    {
        if (PHP_SAPI !== 'cli') {
            exit;
        }
        if (false === extension_loaded('pcntl')) {
            throw new RuntimeException('Missing required pcntl extension');
        }
        #pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        #pcntl_signal(SIGHUP, [$this, 'signalHandler']);
        #pcntl_signal(SIGINT, [$this, 'signalHandler']);
        #pcntl_signal(SIGQUIT, [$this, 'signalHandler']);
        #pcntl_signal(SIGUSR1, [$this, 'signalHandler']);
        #pcntl_signal(SIGUSR2, [$this, 'signalHandler']);
        #pcntl_signal(SIGALRM, [$this, 'alarmHandler']);
        return $this;
    }

    /**
     * get current consumer instance
     *
     * @return FireGento_RabbitMQ_Model_Consumer
     */
    protected function getConsumer()
    {
        if (null === $this->consumer) {
            $this->consumer = Mage::helper('rabbitmq')->getConsumer($this->getArg('consume'));
        }
        return $this->consumer;
    }

    /**
     * Run script
     */
    public function run()
    {
        if ($this->getArg('info')) {
            //show all consumers
        } else if ($this->getArg('consume')) {
            try {
                Mage::dispatchEvent('shell_rabbitmq_consumer_init_process', array('name' => $this->getConsumer()->getActorName(), 'object' => $this->getConsumer()));
                //register amqp event area
                Mage::app()->addEventArea($this->getConsumer()->getActorName());
                $this->getConsumer()->start();
                Mage::dispatchEvent('shell_rabbitmq_consumer_finalize_process', array('name' => $this->getConsumer()->getActorName(), 'object' => $this->getConsumer()));
            } catch (Exception $e) {
                Mage::dispatchEvent('shell_rabbitmq_consumer_finalize_process', array('name' => $this->getConsumer()->getActorName(), 'object' => $this->getConsumer()));
                echo $e->getMessage() . "\n";
            }

        } else {
            echo $this->usageHelp();
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f consumer.php -- [options]

  --consume <consumer>          Consumer name to use
  info                          Show allowed consumers
  help                          This help
USAGE;
    }
}

$shell = new Mage_Shell_Consumer();
$shell->run();