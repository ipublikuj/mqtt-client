<?php
/**
 * Envelope.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:MQTTClient!
 * @subpackage     React
 * @since          1.0.0
 *
 * @date           12.03.17
 */

declare(strict_types = 1);

namespace IPub\MQTTClient\Flow;

use React\Promise;

use BinSoul\Net\Mqtt;

/**
 * Decorates flows with data required for the Client class
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     React
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class Envelope implements Mqtt\Flow
{
	/**
	 * @var Mqtt\Flow
	 */
	private $flow;

	/**
	 * @var Promise\Deferred
	 */
	private $deferred;

	/**
	 * @var Mqtt\Packet
	 */
	private $packet;

	/**
	 * @var bool
	 */
	private $isSilent;

	/**
	 * @param Mqtt\Flow $flow
	 * @param Promise\Deferred $deferred
	 * @param Mqtt\Packet|NULL $packet
	 * @param bool $isSilent
	 */
	public function __construct(
		Mqtt\Flow $flow,
		Promise\Deferred $deferred,
		?Mqtt\Packet $packet = NULL,
		bool $isSilent = FALSE
	) {
		$this->flow = $flow;
		$this->deferred = $deferred;
		$this->packet = $packet;
		$this->isSilent = $isSilent;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCode()
	{
		return $this->flow->getCode();
	}

	/**
	 * {@inheritdoc}
	 */
	public function start()
	{
		$this->packet = $this->flow->start();

		return $this->packet;
	}

	/**
	 * {@inheritdoc}
	 */
	public function accept(Mqtt\Packet $packet)
	{
		return $this->flow->accept($packet);
	}

	/**
	 * {@inheritdoc}
	 */
	public function next(Mqtt\Packet $packet)
	{
		$this->packet = $this->flow->next($packet);

		return $this->packet;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isFinished()
	{
		return $this->flow->isFinished();
	}

	/**
	 * {@inheritdoc}
	 */
	public function isSuccess()
	{
		return $this->flow->isSuccess();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getResult()
	{
		return $this->flow->getResult();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getErrorMessage()
	{
		return $this->flow->getErrorMessage();
	}

	/**
	 * Returns the associated deferred
	 *
	 * @return Promise\Deferred
	 */
	public function getDeferred() : Promise\Deferred
	{
		return $this->deferred;
	}

	/**
	 * Returns the current packet
	 *
	 * @return Mqtt\Packet
	 */
	public function getPacket() : Mqtt\Packet
	{
		return $this->packet;
	}

	/**
	 * Indicates if the flow should emit events
	 *
	 * @return bool
	 */
	public function isSilent() : bool
	{
		return $this->isSilent;
	}
}
