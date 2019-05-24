<?php
/**
 * ClientCommand.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           12.03.17
 */

declare(strict_types = 1);

namespace IPub\MQTTClient\Commands;

use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Style;
use Symfony\Component\Console\Output;

use Psr\Log;

use BinSoul\Net\Mqtt;

use IPub\MQTTClient\Client;
use IPub\MQTTClient\Logger;

/**
 * MQTT client command
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class ClientCommand extends Console\Command\Command
{
	/**
	 * @var Client\IClient
	 */
	private $client;

	/**
	 * @var Log\LoggerInterface|Log\NullLogger|NULL
	 */
	private $logger;

	/**
	 * @param Client\IClient $client
	 * @param Log\LoggerInterface|NULL $logger
	 * @param string|NULL $name
	 */
	public function __construct(
		Client\IClient $client,
		Log\LoggerInterface $logger = NULL,
		string $name = NULL
	) {
		parent::__construct($name);

		$this->client = $client;
		$this->logger = $logger === NULL ? new Log\NullLogger : $logger;
	}

	/**
	 * @return void
	 */
	protected function configure()
	{
		$this
			->setName('ipub:mqttclient:start')
			->setDescription('Start MQTT client.');
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output)
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->text([
			'',
			'+-------------+',
			'| MQTT client |',
			'+-------------+',
			'',
		]);

		if ($this->logger instanceof Logger\Console) {
			$this->logger->setFormatter(new Logger\Formatter\Symfony($io));
		}

		$this->client->onOpen[] = (function (Mqtt\Connection $connection, Client\Client $client) {
			$this->logger->debug(sprintf('Connection to %s opened', $client->getUri()));
		});

		$this->client->connect();

		$this->client->getLoop()->run();
	}
}
