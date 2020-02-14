<?php
/**
 * MQTTClientExtension.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:MQTTClient!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           12.03.17
 */

declare(strict_types = 1);

namespace IPub\MQTTClient\DI;

use Nette;
use Nette\DI;
use Nette\Schema;

use Symfony\Component\EventDispatcher;

use React;

use Psr\Log;

use IPub\MQTTClient;
use IPub\MQTTClient\Client;
use IPub\MQTTClient\Commands;
use IPub\MQTTClient\Configuration;
use IPub\MQTTClient\Events;
use IPub\MQTTClient\Logger;

/**
 * MQTT client extension container
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class MQTTClientExtension extends DI\CompilerExtension
{
	/**
	 * {@inheritdoc}
	 */
	public function getConfigSchema() : Schema\Schema
	{
		return Schema\Expect::structure([
			'broker'     => Schema\Expect::structure([
				'httpHost' => Schema\Expect::string()->nullable(),
				'port'     => Schema\Expect::int(1883),
				'address'  => Schema\Expect::string('127.0.0.1'),
				'dns'      => Schema\Expect::structure([
					'enable'  => Schema\Expect::bool(TRUE),
					'address' => Schema\Expect::string('8.8.8.8'),
				]),
				'secured'  => Schema\Expect::structure([
					'enable'      => Schema\Expect::bool(TRUE),
					'sslSettings' => Schema\Expect::array([]),
				]),
			]),
			'connection' => Schema\Expect::structure([
				'username'  => Schema\Expect::string(''),
				'password'  => Schema\Expect::string(''),
				'clientID'  => Schema\Expect::string(''),
				'keepAlive' => Schema\Expect::int(60),
				'protocol'  => Schema\Expect::int(4),
				'clean'     => Schema\Expect::bool(TRUE),
			]),
			'loop'       => Schema\Expect::anyOf(Schema\Expect::string(), Schema\Expect::type(DI\Definitions\Statement::class))->nullable(),
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();

		if ($configuration->loop === NULL) {
			if ($builder->getByType(React\EventLoop\LoopInterface::class) === NULL) {
				$loop = $builder->addDefinition($this->prefix('client.loop'))
					->setType(React\EventLoop\LoopInterface::class)
					->setFactory('React\EventLoop\Factory::create');

			} else {
				$loop = $builder->getDefinitionByType(React\EventLoop\LoopInterface::class);
			}

		} else {
			$loop = is_string($configuration->loop) ? new DI\Definitions\Statement($configuration->loop) : $configuration->loop;
		}

		$connection = $builder->addDefinition($this->prefix('client.configuration.connection'))
			->setType(Configuration\Connection::class)
			->setArguments([
				'username'  => $configuration->connection->username,
				'password'  => $configuration->connection->password,
				'will'      => NULL,
				'clientID'  => $configuration->connection->clientID,
				'keepAlive' => $configuration->connection->keepAlive,
				'protocol'  => $configuration->connection->protocol,
				'clean'     => $configuration->connection->clean,
			]);

		$brokerConfiguration = $builder->addDefinition($this->prefix('client.configuration'))
			->setType(Configuration\Broker::class)
			->setArguments([
				'httpHost'    => $configuration->broker->httpHost,
				'port'        => $configuration->broker->port,
				'address'     => $configuration->broker->address,
				'enableDNS'   => $configuration->broker->dns->enable,
				'dnsAddress'  => $configuration->broker->dns->address,
				'enableSSL'   => $configuration->broker->secured->enable,
				'sslSettings' => $configuration->broker->secured->sslSettings,
				$connection,
			]);

		if ($builder->findByType(Log\LoggerInterface::class) === []) {
			$builder->addDefinition($this->prefix('server.logger'))
				->setType(Logger\Console::class);
		}

		$builder->addDefinition($this->prefix('client.client'))
			->setType(Client\Client::class)
			->setArguments([
				'eventLoop'     => $loop,
				'configuration' => $brokerConfiguration,
			]);

		if (class_exists('Symfony\Component\Console\Command\Command')) {
			// Define all console commands
			$commands = [
				'client' => Commands\ClientCommand::class,
			];

			foreach ($commands as $name => $cmd) {
				$builder->addDefinition($this->prefix('commands.' . lcfirst($name)))
					->setType($cmd);
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function beforeCompile()
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		if (interface_exists('Symfony\Component\EventDispatcher\EventDispatcherInterface')) {
			$dispatcher = $builder->getDefinition($builder->getByType(EventDispatcher\EventDispatcherInterface::class));

			$client = $builder->getDefinition($builder->getByType(Client\Client::class));
			assert($client instanceof DI\Definitions\ServiceDefinition);

			$client->addSetup('?->onStart[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self',
				$dispatcher,
				new Nette\PhpGenerator\PhpLiteral(Events\StartEvent::class),
			]);
			$client->addSetup('?->onOpen[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self',
				$dispatcher,
				new Nette\PhpGenerator\PhpLiteral(Events\OpenEvent::class),
			]);
			$client->addSetup('?->onConnect[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self',
				$dispatcher,
				new Nette\PhpGenerator\PhpLiteral(Events\ConnectEvent::class),
			]);
			$client->addSetup('?->onDisconnect[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self',
				$dispatcher,
				new Nette\PhpGenerator\PhpLiteral(Events\DisconnectEvent::class),
			]);
			$client->addSetup('?->onClose[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self',
				$dispatcher,
				new Nette\PhpGenerator\PhpLiteral(Events\CloseEvent::class),
			]);
			$client->addSetup('?->onPing[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self',
				$dispatcher,
				new Nette\PhpGenerator\PhpLiteral(Events\PingEvent::class),
			]);
			$client->addSetup('?->onPong[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self',
				$dispatcher,
				new Nette\PhpGenerator\PhpLiteral(Events\PongEvent::class),
			]);
			$client->addSetup('?->onPublish[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self',
				$dispatcher,
				new Nette\PhpGenerator\PhpLiteral(Events\PublishEvent::class),
			]);
			$client->addSetup('?->onSubscribe[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self',
				$dispatcher,
				new Nette\PhpGenerator\PhpLiteral(Events\SubscribeEvent::class),
			]);
			$client->addSetup('?->onUnsubscribe[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self',
				$dispatcher,
				new Nette\PhpGenerator\PhpLiteral(Events\UnsubscribeEvent::class),
			]);
			$client->addSetup('?->onMessage[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self',
				$dispatcher,
				new Nette\PhpGenerator\PhpLiteral(Events\MessageEvent::class),
			]);
			$client->addSetup('?->onWarning[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self',
				$dispatcher,
				new Nette\PhpGenerator\PhpLiteral(Events\WarningEvent::class),
			]);
			$client->addSetup('?->onError[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self',
				$dispatcher,
				new Nette\PhpGenerator\PhpLiteral(Events\ErrorEvent::class),
			]);
		}
	}

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 *
	 * @return void
	 */
	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'mqttClient'
	) :void {
		$config->onCompile[] = function (Nette\Configurator $config, DI\Compiler $compiler) use ($extensionName) {
			$compiler->addExtension($extensionName, new MQTTClientExtension);
		};
	}
}
