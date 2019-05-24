<?php
/**
 * Test: IPub\MQTTClient\Extension
 * @testCase
 *
 * @copyright      More in license.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Tests
 * @since          1.0.0
 *
 * @date           24.05.19
 */

declare(strict_types = 1);

namespace IPubTests\MQTTClient;

use Nette;

use React;

use Tester;
use Tester\Assert;

use IPub\MQTTClient;

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';

/**
 * MQTTClient extension container test case
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Tests
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class ExtensionTest extends Tester\TestCase
{
	public function testCompilersServices()
	{
		$dic = $this->createContainer();

		Assert::true($dic->getService('mqttClient.client.loop') instanceof React\EventLoop\LoopInterface);
		Assert::true($dic->getService('mqttClient.clients.client') instanceof MQTTClient\Client\Client);
	}

	/**
	 * @return Nette\DI\Container
	 */
	protected function createContainer() : Nette\DI\Container
	{
		$config = new Nette\Configurator;
		$config->setTempDirectory(TEMP_DIR);

		$config->addConfig(__DIR__ . DS . 'files' . DS . 'config.neon');

		return $config->createContainer();
	}
}

\run(new ExtensionTest);
