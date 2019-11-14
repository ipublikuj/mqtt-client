<?php
/**
 * PongEvent.php
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

use IPub\MQTTClient\Client;

/**
 * PONG event
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class PongEvent extends EventDispatcher\Event
{
	/**
	 * @var Mqtt\Connection
	 */
	private $connection;

	/**
	 * @var Client\IClient
	 */
	private $client;

	/**
	 * @param Client\IClient $client
	 */
	public function __construct(
		Client\IClient $client
	) {
		$this->client = $client;
	}

	/**
	 * @return Client\IClient
	 */
	public function getClient() : Client\IClient
	{
		return $this->client;
	}
}
