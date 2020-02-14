<?php
/**
 * Broker.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Client
 * @since          1.0.0
 *
 * @date           14.03.17
 */

declare(strict_types = 1);

namespace IPub\MQTTClient\Configuration;

use Nette;

use IPub\MQTTClient\Exceptions;

/**
 * MQTT client broker configuration
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Client
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class Broker
{
	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	/**
	 * @var string
	 */
	private $httpHost;

	/**
	 * @var int
	 */
	private $port;

	/**
	 * @var string
	 */
	private $address;

	/**
	 * @var bool
	 */
	private $enableDNS = TRUE;

	/**
	 * @var string
	 */
	private $dnsAddress;

	/**
	 * @var bool
	 */
	private $enableSSL = FALSE;

	/**
	 * @var array
	 */
	private $sslSettings = [];

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @param string|NULL $httpHost
	 * @param int $port
	 * @param string|NULL $address
	 * @param bool $enableDNS
	 * @param string $dnsAddress
	 * @param bool $enableSSL
	 * @param array $sslSettings
	 * @param Connection $connection
	 */
	public function __construct(
		string $httpHost = NULL,
		int $port = 1883,
		string $address = NULL,
		bool $enableDNS = TRUE,
		string $dnsAddress = '8.8.8.8',
		bool $enableSSL = FALSE,
		array $sslSettings,
		Connection $connection
	) {
		$this->httpHost = $httpHost;
		$this->port = $port;
		$this->address = $address;
		$this->enableDNS = $enableDNS;
		$this->dnsAddress = $dnsAddress;
		$this->enableSSL = $enableSSL;
		$this->sslSettings = $sslSettings;
		$this->connection = $connection;
	}

	/**
	 * @return string
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function getUri() : string
	{
		if ($this->httpHost !== NULL) {
			return $this->httpHost . ':' . $this->port;

		} elseif ($this->address !== NULL) {
			return $this->address . ':' . $this->port;
		}

		throw new Exceptions\InvalidStateException('Broker http host or address is not specified');
	}

	/**
	 * @return string|NULL
	 */
	public function getHttpHost() : ?string
	{
		return $this->httpHost;
	}

	/**
	 * @return int
	 */
	public function getPort() : int
	{
		return $this->port;
	}

	/**
	 * @return string|NULL
	 */
	public function getAddress() : ?string
	{
		return $this->address;
	}

	/**
	 * @return bool
	 */
	public function isDNSEnabled() : bool
	{
		return $this->enableDNS;
	}

	/**
	 * @return string
	 */
	public function getDNSAddress() : string
	{
		return $this->dnsAddress;
	}

	/**
	 * @return bool
	 */
	public function isSSLEnabled() : bool
	{
		return $this->enableSSL;
	}

	/**
	 * @return array
	 */
	public function getSSLConfiguration() : array
	{
		return $this->sslSettings;
	}

	/**
	 * @return Connection
	 */
	public function getConnection() : Connection
	{
		return $this->connection;
	}
}
