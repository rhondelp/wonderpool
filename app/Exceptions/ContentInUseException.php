<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when deleting content that is still referenced (see GuardsDeletion).
 * The message is safe to show to admins.
 */
class ContentInUseException extends RuntimeException
{
}
