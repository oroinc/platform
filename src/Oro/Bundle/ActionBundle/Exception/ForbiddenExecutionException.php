<?php

namespace Oro\Bundle\ActionBundle\Exception;

use Oro\Component\Action\Exception\InvalidConfigurationException;

/**
 * Thrown when action or operation execution is forbidden due to insufficient permissions or restrictions.
 */
class ForbiddenExecutionException extends InvalidConfigurationException
{
}
