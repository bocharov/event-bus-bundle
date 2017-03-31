<?php

namespace Bocharov\EventBusBundle\Command;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class ConsumerCommand
 * @package Bocharov\EventBusBundle\Command
 */
class ConsumerCommand extends \OldSound\RabbitMqBundle\Command\ConsumerCommand
{
    /**
     * @var BaseConsumer
     */
    protected $consumer;

    protected function configure()
    {
        parent::configure();

        $this->setName('eventbus:consumer');
    }

    /**
     * @param $input InputInterface
     */
    protected function initConsumer($input)
    {
        parent::initConsumer($input);

        $this->consumer->setConsumerTag($input->getArgument('name'));
    }
}
