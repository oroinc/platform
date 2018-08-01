<?php

namespace Oro\Bundle\ApiBundle\Processor\Create;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\FormContextTrait;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;

/**
 * The execution context for processors for "create" action.
 */
class CreateContext extends SingleItemContext implements FormContext
{
    use FormContextTrait;
}
