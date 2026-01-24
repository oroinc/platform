<?php

namespace Oro\Bundle\IntegrationBundle\Exception;

/**
 * Thrown when a connector is not found or is invalid for the given integration type.
 *
 * This exception is raised when attempting to access a connector that does not exist
 * for the specified integration type or when the connector configuration is invalid.
 */
class InvalidConnectorException extends InvalidConfigurationException
{
}
