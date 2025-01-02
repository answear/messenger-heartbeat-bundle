# Symfony Messenger Heartbeat bundle

## Installation

* install with Composer
  ```
  composer require answear/messenger-heartbeat-bundle
  ```
  
  `Answear\MessengerHeartbeatBundle\AnswearMessengerHeartbeatBundle::class => ['all' => true],`  
  should be added automatically to your `config/bundles.php` file by Symfony Flex.

* add
`--keepalive` option to your worker command `messenger:consume` and `messenger:failed:retry` to use heartbeat.

Final notes
------------

Feel free to open pull requests with new features, improvements or bug fixes. The Answear team will be grateful for any comments.
