<?php

namespace Oro\Bundle\LayoutBundle\Assetic;

use Assetic\Factory\Loader\FormulaLoaderInterface;
use Assetic\Factory\Resource\ResourceInterface;
use Symfony\Bundle\AsseticBundle\Factory\Resource\ConfigurationResource;

class LayoutFormulaLoader implements FormulaLoaderInterface
{
    /**
     * @inheritdoc
     */
    public function load(ResourceInterface $resource)
    {
        return $resource instanceof LayoutResource ? $resource->getContent() : [];
    }
}
