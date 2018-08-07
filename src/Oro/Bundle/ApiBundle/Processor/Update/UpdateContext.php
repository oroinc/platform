<?php

namespace Oro\Bundle\ApiBundle\Processor\Update;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\FormContextTrait;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;

/**
 * The execution context for processors for "update" action.
 */
class UpdateContext extends SingleItemContext implements FormContext
{
    use FormContextTrait;
}
