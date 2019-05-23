<?php
/**
 * Flow.php
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

namespace IPub\MQTTClient\React;

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
final class Flow implements Mqtt\Flow
{
	/**
	 * @var Flow
	 */
	private $decorated;

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
	 * Constructs an instance of this class.
	 *
	 * @param Mqtt\Flow $decorated
	 * @param Promise\Deferred $deferred
	 * @param Mqtt\Packet $packet
	 * @param bool $isSilent
	 */
	public function __construct(
		Mqtt\Flow $decorated,
		Promise\Deferred $deferred,
		Mqtt\Packet $packet = NULL,
		bool $isSilent = FALSE
	) {
		$this->decorated = $decorated;
		$this->deferred = $deferred;
		$this->packet = $packet;
		$this->isSilent = $isSilent;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCode()
	{
		return $this->decorated->getCode();
	}

	/**
	 * {@inheritdoc}
	 */
	public function start()
	{
		$this->packet = $this->decorated->start();

		return $this->packet;
	}

	/**
	 * {@inheritdoc}
	 */
	public function accept(Mqtt\Packet $packet)
	{
		return $this->decorated->accept($packet);
	}

	/**
	 * {@inheritdoc}
	 */
	public function next(Mqtt\Packet $packet)
	{
		$this->packet = $this->decorated->next($packet);

		return $this->packet;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isFinished()
	{
		return $this->decorated->isFinished();
	}

	/**
	 * {@inheritdoc}
	 */
	public function isSuccess()
	{
		return $this->decorated->isSuccess();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getResult()
	{
		return $this->decorated->getResult();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getErrorMessage()
	{
		return $this->decorated->getErrorMessage();
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
