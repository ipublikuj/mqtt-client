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

use React;

use Psr\Log;

use IPub\MQTTClient;
use IPub\MQTTClient\Client;
use IPub\MQTTClient\Commands;
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
		'broker'     => [
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
		'connection' => [
			'username'  => '',
			'password'  => '',
			'clientID'  => '',
			'keepAlive' => 60,
			'protocol'  => 4,
			'clean'     => TRUE,
		],
		'loop'       => NULL,
		'console'    => FALSE,
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

		$configuration = new Client\Configuration(
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
				'configuration' => $configuration,
			]);

		if ($configuration['console'] === NULL) {
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
