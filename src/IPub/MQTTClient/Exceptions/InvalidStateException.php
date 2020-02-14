<?php
/**
 * InvalidStateException.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:MQTTClient!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           14.03.17
 */

declare(strict_types = 1);

namespace IPub\MQTTClient\Exceptions;

use Exception;

class InvalidStateException extends Exception implements IException
{
}
