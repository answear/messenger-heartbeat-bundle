# Symfony Messenger Heartbeat bundle

## Installation

* install with Composer
```
composer require answear/messenger-heartbeat-bundle
```

`Answear\MessengerHeartbeatBundle\AnswearMessengerHeartbeatBundle::class => ['all' => true],`  
should be added automatically to your `config/bundles.php` file by Symfony Flex.

## Setup

Add middleware as the last element to prevent duplicate messages when RabbitMQ server closes a channel with PRECONDITION_FAILED 
https://www.rabbitmq.com/consumers.html#acknowledgement-timeout

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        default_bus: messenger.bus.default
        buses:
            messenger.bus.default:
                middleware:
                    ...
                    - 'Answear\MessengerHeartbeatBundle\Middleware\TransportExceptionsMiddleware'
                    
```

Final notes
------------

Feel free to open pull requests with new features, improvements or bug fixes. The Answear team will be grateful for any comments.

