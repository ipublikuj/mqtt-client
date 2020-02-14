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

use BinSoul\Net\Mqtt;

/**
 * MQTT client connection configuration
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Client
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class Connection implements Mqtt\Connection
{
	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	/**
	 * @var string
	 */
	private $username;

	/**
	 * @var string
	 */
	private $password;

	/**
	 * @var Mqtt\Message|NULL
	 */
	private $will = NULL;

	/**
	 * @var string
	 */
	private $clientID;

	/**
	 * @var int
	 */
	private $keepAlive;

	/**
	 * @var int
	 */
	private $protocol;

	/**
	 * @var bool
	 */
	private $clean;

	/**
	 * @param string $username
	 * @param string $password
	 * @param Mqtt\Message|NULL $will
	 * @param string $clientID
	 * @param int $keepAlive
	 * @param int $protocol
	 * @param bool $clean
	 */
	public function __construct(
		string $username = '',
		string $password = '',
		Mqtt\Message $will = NULL,
		string $clientID = '',
		int $keepAlive = 60,
		int $protocol = 4,
		bool $clean = TRUE
	) {
		$this->username = $username;
		$this->password = $password;
		$this->will = $will;
		$this->clientID = $clientID;
		$this->keepAlive = $keepAlive;
		$this->protocol = $protocol;
		$this->clean = $clean;
	}

	/**
	 * @return int
	 */
	public function getProtocol() : int
	{
		return $this->protocol;
	}

	/**
	 * @param string $clientID
	 *
	 * @return void
	 */
	public function setClientID(string $clientID) : void
	{
		$this->clientID = $clientID;
	}

	/**
	 * @return string
	 */
	public function getClientID() : string
	{
		return $this->clientID;
	}

	/**
	 * @return bool
	 */
	public function isCleanSession() : bool
	{
		return $this->clean;
	}

	/**
	 * @param string $username
	 *
	 * @return void
	 */
	public function setUsername(string $username) : void
	{
		$this->username = $username;
	}

	/**
	 * @return string
	 */
	public function getUsername() : string
	{
		return $this->username;
	}

	/**
	 * @param string $password
	 *
	 * @return void
	 */
	public function setPassword(string $password) : void
	{
		$this->password = $password;
	}

	/**
	 * @return string
	 */
	public function getPassword() : string
	{
		return $this->password;
	}

	/**
	 * @param Mqtt\Message|NULL $will
	 *
	 * @return void
	 */
	public function setWill(?Mqtt\Message $will) : void
	{
		$this->will = $will;
	}

	/**
	 * @return Mqtt\Message|NULL
	 */
	public function getWill() : ?Mqtt\Message
	{
		return $this->will;
	}

	/**
	 * @return int
	 */
	public function getKeepAlive() : int
	{
		return $this->keepAlive;
	}

	/**
	 * @param string $clientID
	 *
	 * @return self
	 */
	public function withClientID($clientID) : self
	{
		$this->clientID = $clientID;

		return  $this;
	}

	/**
	 * @param string $username
	 * @param string $password
	 *
	 * @return self
	 */
	public function withCredentials($username, $password) : self
	{
		$this->username = $username;
		$this->password = $password;

		return $this;
	}

	/**
	 * @param int $timeout
	 *
	 * @return self
	 */
	public function withKeepAlive($timeout) : self
	{
		$this->keepAlive = $timeout;

		return $this;
	}

	/**
	 * @param int $protocol
	 *
	 * @return self
	 */
	public function withProtocol($protocol) : self
	{
		$this->protocol = $protocol;

		return $this;
	}

	/**
	 * @param Mqtt\Message $will
	 *
	 * @return self
	 */
	public function withWill(Mqtt\Message $will) : self
	{
		$this->will = $will;

		return $this;
	}
}
