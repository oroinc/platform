<?php

namespace Oro\Bundle\SecurityBundle\Exception;

/**
 * Thrown when an ACL condition factor builder cannot be found.
 *
 * This exception is raised when the system cannot locate a required ACL condition
 * factor builder for processing ACL conditions.
 */
class NotFoundAclConditionFactorBuilderException extends \RuntimeException
{
}
