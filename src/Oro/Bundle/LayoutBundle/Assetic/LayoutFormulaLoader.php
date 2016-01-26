<?php

namespace Oro\Bundle\LayoutBundle\Assetic;

use Assetic\Factory\Loader\FormulaLoaderInterface;
use Assetic\Factory\Resource\ResourceInterface;

class LayoutFormulaLoader implements FormulaLoaderInterface
{
    /**
     * @inheritdoc
     */
    public function load(ResourceInterface $resource)
    {
        return $resource->getContent();
    }
}
