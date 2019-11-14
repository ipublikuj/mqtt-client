<?php
/**
 * MessageEvent.php
 *
 * @copyright      More in license.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           14.11.19
 */

namespace IPub\MQTTClient\Events;

use Symfony\Contracts\EventDispatcher;

use BinSoul\Net\Mqtt;

use IPub\MQTTClient\Client;

/**
 * Publish message event
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class MessageEvent extends EventDispatcher\Event
{
	/**
	 * @var Mqtt\Message
	 */
	private $message;

	/**
	 * @var Client\IClient
	 */
	private $client;

	/**
	 * @param Mqtt\Message $message
	 * @param Client\IClient $client
	 */
	public function __construct(
		Mqtt\Message $message,
		Client\IClient $client
	) {
		$this->message = $message;
		$this->client = $client;
	}

	/**
	 * @return Mqtt\Message
	 */
	public function getMessage() : Mqtt\Message
	{
		return $this->message;
	}

	/**
	 * @return Client\IClient
	 */
	public function getClient() : Client\IClient
	{
		return $this->client;
	}
}
