<?php

namespace Bocharov\EventBusBundle\Command;

use OldSound\RabbitMqBundle\Command\SetupFabricCommand;

/**
 * Class ConfigureCommand
 * @package Bocharov\EventBusBundle\Command
 */
class ConfigureCommand extends SetupFabricCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('eventbus:configure');
    }
}
