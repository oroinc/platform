<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

/**
 * Thrown when an error occurs during workflow data serialization or deserialization.
 *
 * This exception indicates that workflow data could not be properly serialized to or
 * deserialized from its storage format.
 */
class SerializerException extends WorkflowException
{
}
