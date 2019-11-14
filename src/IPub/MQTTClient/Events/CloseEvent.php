<?php
/**
 * CloseEvent.php
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
 * Connection close event
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class CloseEvent extends EventDispatcher\Event
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
	 * @param Mqtt\Connection $connection
	 * @param Client\IClient $client
	 */
	public function __construct(
		Mqtt\Connection $connection,
		Client\IClient $client
	) {
		$this->connection = $connection;
		$this->client = $client;
	}

	/**
	 * @return Mqtt\Connection
	 */
	public function getConnection() : Mqtt\Connection
	{
		return $this->connection;
	}

	/**
	 * @return Client\IClient
	 */
	public function getClient() : Client\IClient
	{
		return $this->client;
	}
}
