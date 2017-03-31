<?php

namespace Bocharov\EventBusBundle\DependencyInjection\Compiler;

use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class RegisterAsyncSubscribersPass
 *
 * @package Bocharov\EventBusBundle\DependencyInjection\Compiler
 */
class RegisterAsyncSubscribersPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    protected $dispatcherService;

    /**
     * @var string
     */
    protected $subscriberTag;

    /**
     * RegisterAsyncSubscribersPass constructor.
     *
     * @param string $dispatcherService EventDispatcher service id
     * @param string $subscriberTag     The tag of subscriber
     */
    public function __construct(
        $dispatcherService = 'event_dispatcher',
        $subscriberTag = 'kernel.event_subscriber.async'
    ) {
        $this->dispatcherService = $dispatcherService;
        $this->subscriberTag = $subscriberTag;
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $dispatcherDefinition  = $container->findDefinition(
            $this->dispatcherService
        );
        $partsHolderDefinition = $container->findDefinition(
            'old_sound_rabbit_mq.parts_holder'
        );

        $subscribers = $container->findTaggedServiceIds($this->subscriberTag);
        foreach ($subscribers as $id => $attributes) {
            $dispatcherDefinition->addMethodCall(
                'addSubscriber',
                [
                 new Reference($id),
                 true,
                ]
            );

            $subscriberClass = new ReflectionClass(
                $container->findDefinition($id)->getClass()
            );

            $events = $subscriberClass->getMethod(
                'getSubscribedEvents'
            )->invoke(null);

            $subscriberName = $subscriberClass->getShortName();

            $definition = new Definition('%old_sound_rabbit_mq.consumer.class%');
            $definition->addTag('old_sound_rabbit_mq.base_amqp');
            $definition->addTag('old_sound_rabbit_mq.consumer');

            $definition->addMethodCall(
                'setExchangeOptions',
                array($this->getDefaultExchangeOptions())
            );

            $queueOptions = [
                'name' => $subscriberName,
                'routing_keys' => array_keys($events),
            ] + $this->getDefaultQueueOptions();
            $definition->addMethodCall('setQueueOptions', array($queueOptions));

            $definition->addMethodCall('setCallback', array(array(new Reference($this->dispatcherService), 'execute')));

            $definition->addArgument(new Reference('old_sound_rabbit_mq.connection.default'));

            $name = sprintf('old_sound_rabbit_mq.%s_consumer', $subscriberName);
            $container->setDefinition($name, $definition);

            $partsHolderDefinition->addMethodCall('addPart', array('old_sound_rabbit_mq.base_amqp', new Reference($name)));
            $partsHolderDefinition->addMethodCall('addPart', array('old_sound_rabbit_mq.consumer', new Reference($name)));
        }
    }

    /**
     * Get default AMQP exchange options
     *
     * @return array
     */
    protected function getDefaultExchangeOptions()
    {
        return array(
            'name' => '%event_bus.exchange.name%',
            'type' => 'direct',
            'passive' => false,
            'durable' => true,
            'auto_delete' => false,
            'internal' => false,
            'nowait' => false,
            'declare' => true,
        );
    }

    /**
     * Get default AMQP queue options
     *
     * @return array
     */
    protected function getDefaultQueueOptions()
    {
        return array(
            'passive' => false,
            'durable' => true,
            'exclusive' => false,
            'auto_delete' => false,
            'nowait' => false,
            'declare' => true,
        );
    }
}
