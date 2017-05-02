<?php
/**
 * IClient.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Client
 * @since          1.0.0
 *
 * @date           12.03.17
 */

declare(strict_types = 1);

namespace IPub\MQTTClient\Client;

use React;
use React\EventLoop;
use React\Promise;

use BinSoul\Net\Mqtt;

/**
 * Connection client interface
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Client
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IClient
{
	/**
	 * @param EventLoop\LoopInterface $loop
	 */
	function setLoop(EventLoop\LoopInterface $loop);

	/**
	 * @return EventLoop\LoopInterface
	 */
	function getLoop() : EventLoop\LoopInterface;

	/**
	 * @param Configuration $configuration
	 */
	function setConfiguration(Configuration $configuration);

	/**
	 * @return Configuration
	 */
	function getConfiguration() : Configuration;

	/**
	 * Return the host
	 *
	 * @return string
	 */
	function getUri() : string;

	/**
	 * Return the port
	 *
	 * @return int
	 */
	function getPort() : int;

	/**
	 * Indicates if the client is connected
	 *
	 * @return bool
	 */
	function isConnected() : bool;

	/**
	 * Connects to a broker
	 *
	 * @return Promise\ExtendedPromiseInterface
	 */
	function connect() : Promise\ExtendedPromiseInterface;

	/**
	 * Disconnects from a broker
	 *
	 * @return Promise\ExtendedPromiseInterface
	 */
	function disconnect() : Promise\ExtendedPromiseInterface;

	/**
	 * Subscribes to a topic filter
	 *
	 * @param Mqtt\Subscription $subscription
	 *
	 * @return Promise\ExtendedPromiseInterface
	 */
	function subscribe(Mqtt\Subscription $subscription) : Promise\ExtendedPromiseInterface;

	/**
	 * Unsubscribes from a topic filter
	 *
	 * @param Mqtt\Subscription $subscription
	 *
	 * @return Promise\ExtendedPromiseInterface
	 */
	function unsubscribe(Mqtt\Subscription $subscription) : Promise\ExtendedPromiseInterface;

	/**
	 * Publishes a message
	 *
	 * @param Mqtt\Message $message
	 *
	 * @return Promise\ExtendedPromiseInterface
	 */
	function publish(Mqtt\Message $message) : Promise\ExtendedPromiseInterface;

	/**
	 * Calls the given generator periodically and publishes the return value
	 *
	 * @param int $interval
	 * @param Mqtt\Message $message
	 * @param callable $generator
	 *
	 * @return Promise\ExtendedPromiseInterface
	 */
	function publishPeriodically(int $interval, Mqtt\Message $message, callable $generator) : Promise\ExtendedPromiseInterface;
}
