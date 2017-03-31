# Event Bus Bundle #

## About ##

The Event Bus Bundle represents an **Event Bus** (aka Message Bus, Service Bus) pattern for your Symfony PHP application.

The main purpose to provide comfortable way of processing events *asynchronously* (via RabbitMQ), and *synchronously* (immediately processing Event), using familiar tools as Event Dispatcher, Subscribers, Events.
Same event in different case scenarios can be processed synchronously by one Subscriber and asynchronously by another, which improves your application Modifiability and Maintainability.

## Installation ##

Require the bundle and its dependencies with composer:

```bash
$ composer require bocharov/event-bus-bundle
```

Register the bundle:

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        ...
        new Bocharov\EventBusBundle\EventBusBundle(),
    );
}
```

Enjoy !

## Usage ##

1. Define the following parameters in your parameters.yml file:

    ```yaml
        rabbitmq.host: host
        rabbitmq.port: 5672
        rabbitmq.username: user
        rabbitmq.password: password
        event_bus.exchange.name: event_bus
        event_bus.async_mode_enabled: true
    ```

    Here we configure the connection for RabbitMQ and also specify Exchange name for our Event Bus.
    Turning parameter 'event_bus.async_mode_enabled' on makes event dispatcher to process all events synchronously (for testing/debugging purposes).

2. Create Subscriber class similar to this:

    ```php
        <?php

        namespace App\Bundle\UserBundle\EventListener;

        use Symfony\Component\EventDispatcher\EventSubscriberInterface;

        use App\Bundle\UserBundle\AppUserEvents;
        use App\Bundle\UserBundle\Event\UserEvent;

        class SearchSubscriber implements EventSubscriberInterface
        {
            /**
             * @return array
             */
            public static function getSubscribedEvents(): array
            {
                return [
                    AppUserEvents::USER_CREATE => 'onUserCreated',
                ];
            }

            public function onUserCreated(UserEvent $event)
            {
                //some event processing logic should be here
            }
        }
    ```

3. Register your subscriber service with **kernel.event_subscriber.async** tag

    ```yaml
    services:
        user.subscriber.search_subscriber:
            class: 'App\Bundle\UserBundle\EventListener\SearchSubscriber'
            tags:
                - { name: kernel.event_subscriber.async}
    ```

4. Run the following command to create all needed exchanges, queues and bindings

    ```bash
    php bin/console eventbus:configure
    ```
    Queues' names are subscribers' classes names, which have **kernel.event_subscriber.async** tag.
    Bindings are created based on **getSubscribedEvents** method - routing_key = event name.
    After that you can open RabbitMQ admin and check if everything created correctly.

5. From now on you can publish events using standard **event_dispatcher** service via dispatch method.

    ```php
        $this->dispatcher->dispatch(AppUserEvents::USER_CREATE, (new UserEvent($user)));
    ```

6. To consume messages from Event Bus simply run a worker like this:

    ```bash
    php bin/console eventbus:consumer SearchSubscriber
    ```
    Worker will consume messages in infinite loop and pass them to corresponding subscriber's method, based on event name.

7. PROFIT!
