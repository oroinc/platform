<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\FormContextTrait;

/**
 * The execution context for processors for "update_subresource", "add_subresource"
 * and "delete_subresource" actions.
 */
class ChangeSubresourceContext extends SubresourceContext implements FormContext
{
    use FormContextTrait;
}
