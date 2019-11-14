<?php
/**
 * ErrorEvent.php
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

use Exception;

use Symfony\Contracts\EventDispatcher;

use BinSoul\Net\Mqtt;

use IPub\MQTTClient\Client;

/**
 * Communication error event
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class ErrorEvent extends EventDispatcher\Event
{
	/**
	 * @var Exception
	 */
	private $exception;

	/**
	 * @var Client\IClient
	 */
	private $client;

	/**
	 * @param Exception $exception
	 * @param Client\IClient $client
	 */
	public function __construct(
		Exception $exception,
		Client\IClient $client
	) {
		$this->exception = $exception;
		$this->client = $client;
	}

	/**
	 * @return Exception
	 */
	public function getException() : Exception
	{
		return $this->exception;
	}

	/**
	 * @return Client\IClient
	 */
	public function getClient() : Client\IClient
	{
		return $this->client;
	}
}
