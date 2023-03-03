<?php

namespace Oro\Bundle\EntityExtendBundle\Twig;

use Oro\Bundle\EntityExtendBundle\Twig\NodeVisitor\GetAttrNodeVisitor;
use Twig\Extension\AbstractExtension;

/**
 * Registers GetAttributeNodeExtension to add comments to twig templates
 */
class GetAttributeNodeExtension extends AbstractExtension
{
    /**
     * {@inheritDoc}
     */
    public function getNodeVisitors()
    {
        return [new GetAttrNodeVisitor()];
    }
}
