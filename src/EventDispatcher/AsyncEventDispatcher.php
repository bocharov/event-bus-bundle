<?php

namespace Bocharov\EventBusBundle\EventDispatcher;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class AsyncEventDispatcher
 * @package Bocharov\EventBusBundle\EventDispatcher
 */
class AsyncEventDispatcher extends ContainerAwareEventDispatcher implements ConsumerInterface
{
    /**
     * @var ProducerInterface
     */
    protected $producer;

    /**
     * @var array
     */
    protected $asyncListeners;

    /**
     * @var bool
     */
    protected $asyncModeEnabled;

    /**
     * Class constructor
     *
     * @param ContainerInterface $container
     * @param ProducerInterface  $producer
     * @param bool               $asyncModeEnabled
     */
    public function __construct(
        ContainerInterface $container,
        ProducerInterface $producer,
        bool $asyncModeEnabled
    ) {
        $this->producer = $producer;
        $this->asyncModeEnabled = $asyncModeEnabled;

        parent::__construct($container);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($eventName, Event $event = null)
    {
        if ($this->asyncModeEnabled && $this->getAsyncListeners($eventName)) {
            $message = serialize($event);
            $this->producer->publish($message, $eventName);
        }

        return parent::dispatch($eventName, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(AMQPMessage $message)
    {
        //1. reconstruct event from message->body
        //2. dispatch event through needed subscriber/listener based on queue name = consumer tag
        //3. return false if failed to process due some reason

        $event = unserialize($message->body);
        $consumerTag = $message->get('consumer_tag');
        $eventName = $message->get('routing_key');

        if ($listeners = $this->getAsyncListeners($eventName)) {
            foreach ($listeners as $listener) {
                if (strpos(get_class($listener[0]), $consumerTag) !== false) {
                    try {
                        call_user_func($listener, $event, $eventName, $this);
                    } catch (\Exception $e) {
                        print_r($e->getMessage());
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param EventSubscriberInterface $subscriber
     * @param bool                     $async
     */
    public function addSubscriber(EventSubscriberInterface $subscriber, bool $async = false)
    {
        if ($this->asyncModeEnabled && $async) {
            foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
                if (is_string($params)) {
                    $this->addAsyncListener($eventName, array($subscriber, $params));
                } elseif (is_string($params[0])) {
                    $this->addAsyncListener($eventName, array($subscriber, $params[0]));
                } else {
                    foreach ($params as $listener) {
                        $this->addAsyncListener($eventName, array($subscriber, $listener[0]));
                    }
                }
            }
        } else {
            parent::addSubscriber($subscriber);
        }
    }

    /**
     * @param $eventName
     * @param $listener
     */
    protected function addAsyncListener($eventName, $listener)
    {
        $this->asyncListeners[$eventName][] = $listener;
    }

    /**
     * @param $eventName
     * @return array
     */
    protected function getAsyncListeners($eventName)
    {
        return $this->asyncListeners[$eventName] ?? [];
    }
}
