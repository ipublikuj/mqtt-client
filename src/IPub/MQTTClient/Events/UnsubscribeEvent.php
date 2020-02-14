<?php
/**
 * UnsubscribeEvent.php
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
 * Unsubscribe to topic event
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class UnsubscribeEvent extends EventDispatcher\Event
{
	/**
	 * @var Mqtt\Subscription
	 */
	private $subscription;

	/**
	 * @var Client\IClient
	 */
	private $client;

	/**
	 * @param Mqtt\Subscription $subscription
	 * @param Client\IClient $client
	 */
	public function __construct(
		Mqtt\Subscription $subscription,
		Client\IClient $client
	) {
		$this->subscription = $subscription;
		$this->client = $client;
	}

	/**
	 * @return Mqtt\Subscription
	 */
	public function getSubscription() : Mqtt\Subscription
	{
		return $this->subscription;
	}

	/**
	 * @return Client\IClient
	 */
	public function getClient() : Client\IClient
	{
		return $this->client;
	}
}
