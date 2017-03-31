<?php

namespace Bocharov\EventBusBundle;

use Bocharov\EventBusBundle\DependencyInjection\Compiler\RegisterAsyncSubscribersPass;
use OldSound\RabbitMqBundle\DependencyInjection\Compiler\RegisterPartsPass;
use OldSound\RabbitMqBundle\DependencyInjection\OldSoundRabbitMqExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class EventBusBundle
 * @package Bocharov\EventBusBundle
 */
class EventBusBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->registerExtension(new OldSoundRabbitMqExtension());
        $container->addCompilerPass(new RegisterPartsPass());
        $container->addCompilerPass(new RegisterAsyncSubscribersPass());
    }
}
