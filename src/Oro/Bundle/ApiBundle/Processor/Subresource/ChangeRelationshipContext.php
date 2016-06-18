<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\FormContextTrait;

class ChangeRelationshipContext extends SubresourceContext implements FormContext
{
    use FormContextTrait;

    /**
     * {@inheritdoc}
     */
    protected function createParentConfigExtras()
    {
        return [
            new EntityDefinitionConfigExtra('update'),
            new FilterFieldsConfigExtra([$this->getParentClassName() => [$this->getAssociationName()]])
        ];
    }
}
