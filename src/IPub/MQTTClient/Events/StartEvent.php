<?php
/**
 * StartEvent.php
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

/**
 * Server start event
 *
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class StartEvent extends EventDispatcher\Event
{

}
