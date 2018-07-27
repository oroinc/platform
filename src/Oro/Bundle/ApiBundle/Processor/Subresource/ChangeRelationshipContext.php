<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\FormContextTrait;

/**
 * The base execution context for processors for "update_relationship", "add_relationship"
 * and "delete_relationship" actions.
 */
class ChangeRelationshipContext extends SubresourceContext implements FormContext
{
    use FormContextTrait;
}
