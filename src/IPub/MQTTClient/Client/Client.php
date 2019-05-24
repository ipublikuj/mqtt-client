<?php
/**
 * Client.php
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

use Nette;

use React\EventLoop;
use React\Dns;
use React\Promise;
use React\Socket;
use React\Stream;

use BinSoul\Net\Mqtt;

use IPub\MQTTClient\Exceptions;
use IPub\MQTTClient\Flow;

/**
 * Connection client
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Client
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 *
 * @method onOpen(Mqtt\Connection $connection, IClient $client)
 * @method onConnect(Mqtt\Connection $connection, IClient $client)
 * @method onDisconnect(Mqtt\Connection $connection, IClient $client)
 * @method onClose(Mqtt\Connection $connection, IClient $client)
 * @method onPing(IClient $client)
 * @method onPong(IClient $client)
 * @method onPublish(Mqtt\Message $message, IClient $client)
 * @method onSubscribe(Mqtt\Subscription $subscription, IClient $client)
 * @method onUnsubscribe(Mqtt\Subscription $subscription, IClient $client)
 * @method onMessage(Mqtt\Message $message, IClient $client)
 * @method onWarning(\Exception $ex, IClient $client)
 * @method onError(\Exception $ex, IClient $client)
 */
final class Client implements IClient
{
	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	/**
	 * @var \Closure
	 */
	public $onOpen = [];

	/**
	 * @var \Closure
	 */
	public $onConnect = [];

	/**
	 * @var \Closure
	 */
	public $onDisconnect = [];

	/**
	 * @var \Closure
	 */
	public $onClose = [];

	/**
	 * @var \Closure
	 */
	public $onPing = [];

	/**
	 * @var \Closure
	 */
	public $onPong = [];

	/**
	 * @var \Closure
	 */
	public $onPublish = [];

	/**
	 * @var \Closure
	 */
	public $onSubscribe = [];

	/**
	 * @var \Closure
	 */
	public $onUnsubscribe = [];

	/**
	 * @var \Closure
	 */
	public $onMessage = [];

	/**
	 * @var \Closure
	 */
	public $onWarning = [];

	/**
	 * @var \Closure
	 */
	public $onError = [];

	/**
	 * @var EventLoop\LoopInterface
	 */
	private $loop;

	/**
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * @var Socket\ConnectorInterface
	 */
	private $connector;

	/**
	 * @var Socket\ConnectionInterface|NULL
	 */
	private $stream = NULL;

	/**
	 * @var Mqtt\Connection
	 */
	private $connection;

	/**
	 * @var Mqtt\StreamParser
	 */
	private $parser;

	/**
	 * @var Mqtt\IdentifierGenerator
	 */
	private $identifierGenerator;

	/**
	 * @var bool
	 */
	private $isConnected = FALSE;

	/**
	 * @var bool
	 */
	private $isConnecting = FALSE;

	/**
	 * @var bool
	 */
	private $isDisconnecting = FALSE;

	/**
	 * @var string
	 */
	private $host;

	/**
	 * @var int
	 */
	private $port;

	/**
	 * @var int
	 */
	private $timeout = 5;

	/**
	 * @var Flow\Envelope[]
	 */
	private $receivingFlows = [];

	/**
	 * @var Flow\Envelope[]
	 */
	private $sendingFlows = [];

	/**
	 * @var Flow\Envelope
	 */
	private $writtenFlow;

	/**
	 * @var EventLoop\Timer\TimerInterface[]
	 */
	private $timer = [];

	/**
	 * @param EventLoop\LoopInterface $eventLoop
	 * @param Configuration $configuration
	 * @param Mqtt\IdentifierGenerator|NULL $identifierGenerator
	 * @param Mqtt\StreamParser|NULL $parser
	 */
	public function __construct(
		EventLoop\LoopInterface $eventLoop,
		Configuration $configuration,
		Mqtt\IdentifierGenerator $identifierGenerator = NULL,
		Mqtt\StreamParser $parser = NULL
	) {
		$this->loop = $eventLoop;
		$this->configuration = $configuration;

		$this->createConnector();

		$this->parser = $parser;

		if ($this->parser === NULL) {
			$this->parser = new Mqtt\StreamParser;
		}

		$this->parser->onError(function (\Exception $ex) {
			$this->onError($ex, $this);
		});

		$this->identifierGenerator = $identifierGenerator;

		if ($this->identifierGenerator === NULL) {
			$this->identifierGenerator = new Mqtt\DefaultIdentifierGenerator;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function setLoop(EventLoop\LoopInterface $loop) : void
	{
		if (!$this->isConnected && !$this->isConnecting) {
			$this->loop = $loop;

			$this->createConnector();

		} else {
			throw new Exceptions\LogicException('Connection is already established. React event loop could not be changed.');
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLoop() : EventLoop\LoopInterface
	{
		return $this->loop;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setConfiguration(Configuration $configuration) : void
	{
		if ($this->isConnected() || $this->isConnecting) {
			throw new Exceptions\InvalidStateException('Client is connecting or connected to the broker, therefore configuration could not be changed.');
		}

		$this->configuration = $configuration;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUri() : string
	{
		return $this->configuration->getUri();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPort() : int
	{
		return $this->configuration->getPort();
	}

	/**
	 * {@inheritdoc}
	 */
	public function isConnected() : bool
	{
		return $this->isConnected;
	}

	/**
	 * {@inheritdoc}
	 */
	public function connect() : Promise\ExtendedPromiseInterface
	{
		if ($this->isConnected || $this->isConnecting) {
			return new Promise\RejectedPromise(new Exceptions\LogicException('The client is already connected.'));
		}

		$this->isConnecting = TRUE;
		$this->isConnected = FALSE;

		$connection = $this->configuration->getConnection();

		if ($connection->getClientID() === '') {
			$connection = $connection->withClientID($this->identifierGenerator->generateClientID());
		}

		$deferred = new Promise\Deferred;

		$this->establishConnection()
			->then(function (Socket\ConnectionInterface $stream) use ($connection, $deferred) {
				$this->stream = $stream;

				$this->onOpen($connection, $this);

				$this->registerClient($connection)
					->then(function (Mqtt\Connection $connection) use ($deferred) {
						$this->isConnecting = FALSE;
						$this->isConnected = TRUE;

						$this->connection = $connection;

						$this->onConnect($connection, $this);

						$deferred->resolve($this->connection);
					})
					->otherwise(function (\Exception $ex) use ($stream, $deferred, $connection) {
						$this->isConnecting = FALSE;

						$this->onError($ex, $this);

						$deferred->reject($ex);

						if ($this->stream !== NULL) {
							$this->stream->close();
						}

						$this->onClose($connection, $this);
					});
			})
			->otherwise(function (\Exception $ex) use ($deferred) {
				$this->isConnecting = FALSE;

				$this->onError($ex, $this);

				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * {@inheritdoc}
	 */
	public function disconnect() : Promise\ExtendedPromiseInterface
	{
		if (!$this->isConnected || $this->isDisconnecting) {
			return new Promise\RejectedPromise(new Exceptions\LogicException('The client is not connected.'));
		}

		$this->isDisconnecting = TRUE;

		$deferred = new Promise\Deferred;

		$this->startFlow(new Mqtt\Flow\OutgoingDisconnectFlow($this->connection), TRUE)
			->then(function (Mqtt\Connection $connection) use ($deferred) {
				$this->isDisconnecting = FALSE;
				$this->isConnected = FALSE;

				$this->onDisconnect($connection, $this);

				$deferred->resolve($connection);

				if ($this->stream !== NULL) {
					$this->stream->close();
				}
			})
			->otherwise(function () use ($deferred) {
				$this->isDisconnecting = FALSE;
				$deferred->reject($this->connection);
			});

		return $deferred->promise();
	}

	/**
	 * {@inheritdoc}
	 */
	public function subscribe(Mqtt\Subscription $subscription) : Promise\ExtendedPromiseInterface
	{
		if (!$this->isConnected) {
			return new Promise\RejectedPromise(new Exceptions\LogicException('The client is not connected.'));
		}

		return $this->startFlow(new Mqtt\Flow\OutgoingSubscribeFlow([$subscription], $this->identifierGenerator));
	}

	/**
	 * {@inheritdoc}
	 */
	public function unsubscribe(Mqtt\Subscription $subscription) : Promise\ExtendedPromiseInterface
	{
		if (!$this->isConnected) {
			return new Promise\RejectedPromise(new Exceptions\LogicException('The client is not connected.'));
		}

		return $this->startFlow(new Mqtt\Flow\OutgoingUnsubscribeFlow([$subscription], $this->identifierGenerator));
	}

	/**
	 * {@inheritdoc}
	 */
	public function publish(Mqtt\Message $message) : Promise\ExtendedPromiseInterface
	{
		if (!$this->isConnected) {
			return new Promise\RejectedPromise(new Exceptions\LogicException('The client is not connected.'));
		}

		return $this->startFlow(new Mqtt\Flow\OutgoingPublishFlow($message, $this->identifierGenerator));
	}

	/**
	 * {@inheritdoc}
	 */
	public function publishPeriodically(
		int $interval,
		Mqtt\Message $message,
		callable $generator
	) : Promise\ExtendedPromiseInterface {
		if (!$this->isConnected) {
			return new Promise\RejectedPromise(new Exceptions\LogicException('The client is not connected.'));
		}

		$deferred = new Promise\Deferred;

		$this->timer[] = $this->loop->addPeriodicTimer($interval, function () use ($message, $generator, $deferred) {
			$this->publish($message->withPayload($generator($message->getTopic())))
				->then(
					function ($value) use ($deferred) {
						$deferred->notify($value);
					},
					function (\Exception $e) use ($deferred) {
						$deferred->reject($e);
					}
				);
		});

		return $deferred->promise();
	}

	/**
	 * Establishes a network connection to a server
	 *
	 * @return Promise\ExtendedPromiseInterface
	 */
	private function establishConnection() : Promise\ExtendedPromiseInterface
	{
		$deferred = new Promise\Deferred;

		$timer = $this->loop->addTimer($this->timeout, function () use ($deferred) {
			$exception = new Exceptions\RuntimeException(sprintf('Connection timed out after %d seconds.', $this->timeout));

			$deferred->reject($exception);
		});

		$this->connector->connect($this->configuration->getUri())
			->always(function () use ($timer) {
				$this->loop->cancelTimer($timer);
			})
			->then(function (Stream\DuplexStreamInterface $stream) use ($deferred) {
				$stream->on('data', function ($data) {
					$this->handleReceive($data);
				});

				$stream->on('close', function () {
					$this->handleClose();
				});

				$stream->on('error', function (\Exception $ex) {
					$this->handleError($ex);
				});

				$deferred->resolve($stream);
			})
			->otherwise(function (\Exception $ex) use ($deferred) {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * Registers a new client with the broker
	 *
	 * @param Mqtt\Connection $connection
	 *
	 * @return Promise\ExtendedPromiseInterface
	 */
	private function registerClient(Mqtt\Connection $connection) : Promise\ExtendedPromiseInterface
	{
		$deferred = new Promise\Deferred;

		$responseTimer = $this->loop->addTimer($this->timeout, function () use ($deferred) {
			$exception = new Exceptions\RuntimeException(sprintf('No response after %d seconds.', $this->timeout));

			$deferred->reject($exception);
		});

		$this->startFlow(new Mqtt\Flow\OutgoingConnectFlow($connection, $this->identifierGenerator), TRUE)
			->always(function () use ($responseTimer) {
				$this->loop->cancelTimer($responseTimer);

			})->then(function (Mqtt\Connection $connection) use ($deferred) {
				$this->timer[] = $this->loop->addPeriodicTimer(floor($connection->getKeepAlive() * 0.75), function () {
					$this->startFlow(new Mqtt\Flow\OutgoingPingFlow);
				});

				$deferred->resolve($connection);

			})->otherwise(function (\Exception $ex) use ($deferred) {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * Handles incoming data
	 *
	 * @param string $data
	 *
	 * @return void
	 */
	private function handleReceive(string $data) : void
	{
		if (!$this->isConnected && !$this->isConnecting) {
			return;
		}

		$flowCount = count($this->receivingFlows);

		$packets = $this->parser->push($data);

		foreach ($packets as $packet) {
			$this->handlePacket($packet);
		}

		if ($flowCount > count($this->receivingFlows)) {
			$this->receivingFlows = array_values($this->receivingFlows);
		}
	}

	/**
	 * Handles an incoming packet
	 *
	 * @param Mqtt\Packet $packet
	 *
	 * @return void
	 */
	private function handlePacket(Mqtt\Packet $packet) : void
	{
		switch ($packet->getPacketType()) {
			case Mqtt\Packet::TYPE_PUBLISH:
				/* @var Mqtt\Packet\PublishRequestPacket $packet */
				$message = new Mqtt\DefaultMessage(
					$packet->getTopic(),
					$packet->getPayload(),
					$packet->getQosLevel(),
					$packet->isRetained(),
					$packet->isDuplicate()
				);

				$this->startFlow(new Mqtt\Flow\IncomingPublishFlow($message, $packet->getIdentifier()));
				break;

			case Mqtt\Packet::TYPE_CONNACK:
			case Mqtt\Packet::TYPE_PINGRESP:
			case Mqtt\Packet::TYPE_SUBACK:
			case Mqtt\Packet::TYPE_UNSUBACK:
			case Mqtt\Packet::TYPE_PUBREL:
			case Mqtt\Packet::TYPE_PUBACK:
			case Mqtt\Packet::TYPE_PUBREC:
			case Mqtt\Packet::TYPE_PUBCOMP:
				$flowFound = FALSE;

				foreach ($this->receivingFlows as $index => $flow) {
					if ($flow->accept($packet)) {
						$flowFound = TRUE;

						unset($this->receivingFlows[$index]);
						$this->continueFlow($flow, $packet);

						break;
					}
				}

				if (!$flowFound) {
					$ex = new Exceptions\LogicException(sprintf('Received unexpected packet of type %d.', $packet->getPacketType()));

					$this->onWarning($ex, $this);
				}
				break;

			default:
				$ex = new Exceptions\LogicException(sprintf('Cannot handle packet of type %d.', $packet->getPacketType()));

				$this->onWarning($ex, $this);
		}
	}

	/**
	 * Handles outgoing packets
	 *
	 * @return void
	 */
	private function handleSend() : void
	{
		$flow = NULL;

		if ($this->writtenFlow !== NULL) {
			$flow = $this->writtenFlow;
			$this->writtenFlow = NULL;
		}

		if (count($this->sendingFlows) > 0) {
			$this->writtenFlow = array_shift($this->sendingFlows);
			$this->stream->write($this->writtenFlow->getPacket());
		}

		if ($flow !== NULL) {
			if ($flow->isFinished()) {
				$this->loop->nextTick(function () use ($flow) {
					$this->finishFlow($flow);
				});

			} else {
				$this->receivingFlows[] = $flow;
			}
		}
	}

	/**
	 * Handles closing of the stream
	 *
	 * @return void
	 */
	private function handleClose() : void
	{
		foreach ($this->timer as $timer) {
			$this->loop->cancelTimer($timer);
		}

		$connection = $this->connection;

		$this->isConnecting = FALSE;
		$this->isDisconnecting = FALSE;
		$this->isConnected = FALSE;
		$this->connection = NULL;
		$this->stream = NULL;

		if ($connection !== NULL) {
			$this->onClose($connection, $this);
		}
	}

	/**
	 * Handles errors of the stream
	 *
	 * @param \Exception $ex
	 *
	 * @return void
	 */
	private function handleError(\Exception $ex) : void
	{
		$this->onError($ex, $this);
	}

	/**
	 * Starts the given flow
	 *
	 * @param Mqtt\Flow $flow
	 * @param bool $isSilent
	 *
	 * @return Promise\ExtendedPromiseInterface
	 */
	private function startFlow(Mqtt\Flow $flow, bool $isSilent = FALSE) : Promise\ExtendedPromiseInterface
	{
		try {
			$packet = $flow->start();

		} catch (\Exception $ex) {
			$this->onError($ex, $this);

			return new Promise\RejectedPromise($ex);
		}

		$deferred = new Promise\Deferred;
		$internalFlow = new Flow\Envelope($flow, $deferred, $packet, $isSilent);

		if ($packet !== NULL) {
			if ($this->writtenFlow !== NULL) {
				$this->sendingFlows[] = $internalFlow;

			} else {
				$this->stream->write($packet);
				$this->writtenFlow = $internalFlow;

				$this->handleSend();
			}

		} else {
			$this->loop->nextTick(function () use ($internalFlow) {
				$this->finishFlow($internalFlow);
			});
		}

		return $deferred->promise();
	}

	/**
	 * Continues the given flow
	 *
	 * @param Flow\Envelope $flow
	 * @param Mqtt\Packet $packet
	 *
	 * @return void
	 */
	private function continueFlow(Flow\Envelope $flow, Mqtt\Packet $packet) : void
	{
		try {
			$response = $flow->next($packet);

		} catch (\Exception $ex) {
			$this->onError($ex, $this);

			return;
		}

		if ($response !== NULL) {
			if ($this->writtenFlow !== NULL) {
				$this->sendingFlows[] = $flow;

			} else {
				$this->stream->write($response);
				$this->writtenFlow = $flow;

				$this->handleSend();
			}

		} elseif ($flow->isFinished()) {
			$this->loop->nextTick(function () use ($flow) {
				$this->finishFlow($flow);
			});
		}
	}

	/**
	 * Finishes the given flow
	 *
	 * @param Flow\Envelope $flow
	 *
	 * @return void
	 */
	private function finishFlow(Flow\Envelope $flow) : void
	{
		if ($flow->isSuccess()) {
			if (!$flow->isSilent()) {
				switch ($flow->getCode()) {
					case 'ping':
						$this->onPing($this);
						break;

					case 'pong':
						$this->onPong($this);
						break;

					case 'connect':
						$this->onConnect($flow->getResult(), $this);
						break;

					case 'disconnect':
						$this->onDisconnect($flow->getResult(), $this);
						break;

					case 'publish':
						$this->onPublish($flow->getResult(), $this);
						break;

					case 'subscribe':
						$this->onSubscribe($flow->getResult(), $this);
						break;

					case 'unsubscribe':
						$this->onUnsubscribe($flow->getResult(), $this);
						break;

					case 'message':
						$this->onMessage($flow->getResult(), $this);
						break;
				}
			}

			$flow->getDeferred()->resolve($flow->getResult());

		} else {
			$ex = new Exceptions\RuntimeException($flow->getErrorMessage());
			$this->onWarning($ex, $this);

			$flow->getDeferred()->reject($ex);
		}
	}

	/**
	 * @return void
	 */
	private function createConnector() : void
	{
		$this->connector = new Socket\TcpConnector($this->loop);

		if ($this->configuration->isDNSEnabled()) {
			$dnsResolverFactory = new Dns\Resolver\Factory;

			$this->connector = new Socket\DnsConnector($this->connector, $dnsResolverFactory->createCached($this->configuration->getDNSAddress(), $this->loop));
		}

		if ($this->configuration->isSSLEnabled()) {
			$this->connector = new Socket\SecureConnector($this->connector, $this->loop, $this->configuration->getSSLConfiguration());
		}
	}
}
