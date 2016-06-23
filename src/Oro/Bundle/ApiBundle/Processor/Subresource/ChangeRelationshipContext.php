<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\FormContextTrait;

class ChangeRelationshipContext extends SubresourceContext implements FormContext
{
    use FormContextTrait;
}
