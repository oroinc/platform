<?php

namespace Oro\Bundle\ApiBundle\Processor\Update;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\FormContextTrait;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;

class UpdateContext extends SingleItemContext implements FormContext
{
    use FormContextTrait;
}
