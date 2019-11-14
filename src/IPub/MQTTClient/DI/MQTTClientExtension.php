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
use Nette\PhpGenerator as Code;

use BinSoul\Net\Mqtt;

use Symfony\Component\EventDispatcher;

use React;

use Psr\Log;

use IPub\MQTTClient;
use IPub\MQTTClient\Client;
use IPub\MQTTClient\Commands;
use IPub\MQTTClient\Events;
use IPub\MQTTClient\Logger;

/**
 * MQTT client extension container
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 *
 * @method DI\ContainerBuilder getContainerBuilder()
 * @method array getConfig(array $default)
 * @method string prefix($id)
 */
final class MQTTClientExtension extends DI\CompilerExtension
{
	/**
	 * @var array
	 */
	private $defaults = [
		'broker'       => [
			'httpHost' => NULL,
			'port'     => 1883,
			'address'  => NULL,
			'dns'      => [
				'enable'  => TRUE,
				'address' => '8.8.8.8',
			],
			'secured'  => [
				'enable'      => FALSE,
				'sslSettings' => [],
			],
		],
		'connection'   => [
			'username'  => '',
			'password'  => '',
			'clientID'  => '',
			'keepAlive' => 60,
			'protocol'  => 4,
			'clean'     => TRUE,
		],
		'loop'         => NULL,
		'console'      => FALSE,
		'symfonyEvets' => FALSE,
	];

	/**
	 * {@inheritdoc}
	 */
	public function loadConfiguration()
	{
		// Get container builder
		$builder = $this->getContainerBuilder();

		// Merge extension default config
		$this->setConfig(DI\Config\Helpers::merge($this->config, DI\Helpers::expand($this->defaults, $builder->parameters)));

		// Get extension configuration
		$configuration = $this->getConfig();

		if ($configuration['loop'] === NULL) {
			if ($builder->getByType(React\EventLoop\LoopInterface::class) === NULL) {
				$loop = $builder->addDefinition($this->prefix('client.loop'))
					->setType(React\EventLoop\LoopInterface::class)
					->setFactory('React\EventLoop\Factory::create');

			} else {
				$loop = $builder->getDefinitionByType(React\EventLoop\LoopInterface::class);
			}

		} else {
			$loop = $builder->getDefinition(ltrim($configuration['loop'], '@'));
		}

		$connection = new Mqtt\DefaultConnection(
			$configuration['connection']['username'],
			$configuration['connection']['password'],
			NULL,
			$configuration['connection']['clientID'],
			$configuration['connection']['keepAlive'],
			$configuration['connection']['protocol'],
			$configuration['connection']['clean']
		);

		$clientConfiguration = new Client\Configuration(
			$configuration['broker']['httpHost'],
			$configuration['broker']['port'],
			$configuration['broker']['address'],
			$configuration['broker']['dns']['enable'],
			$configuration['broker']['dns']['address'],
			$configuration['broker']['secured']['enable'],
			$configuration['broker']['secured']['sslSettings'],
			$connection
		);

		if ($builder->findByType(Log\LoggerInterface::class) === []) {
			$builder->addDefinition($this->prefix('server.logger'))
				->setType(Logger\Console::class);
		}

		$builder->addDefinition($this->prefix('client.client'))
			->setType(Client\Client::class)
			->setArguments([
				'eventLoop'     => $loop,
				'configuration' => $clientConfiguration,
			]);

		if ($configuration['console'] === TRUE) {
			// Define all console commands
			$commands = [
				'client' => Commands\ClientCommand::class,
			];

			foreach ($commands as $name => $cmd) {
				$builder->addDefinition($this->prefix('commands' . lcfirst($name)))
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

		// Get container builder
		$builder = $this->getContainerBuilder();

		// Merge extension default config
		$this->setConfig(DI\Config\Helpers::merge($this->config, DI\Helpers::expand($this->defaults, $builder->parameters)));

		// Get extension configuration
		$configuration = $this->getConfig();

		// Get container builder
		$builder = $this->getContainerBuilder();

		if ($configuration['symfonyEvets'] === TRUE) {
			$dispatcher = $builder->getDefinition($builder->getByType(EventDispatcher\EventDispatcherInterface::class));

			$client = $builder->getDefinition($builder->getByType(Client\Client::class));
			assert($client instanceof DI\ServiceDefinition);

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
	public static function register(Nette\Configurator $config, string $extensionName = 'mqttClient')
	{
		$config->onCompile[] = function (Nette\Configurator $config, DI\Compiler $compiler) use ($extensionName) {
			$compiler->addExtension($extensionName, new MQTTClientExtension);
		};
	}
}
