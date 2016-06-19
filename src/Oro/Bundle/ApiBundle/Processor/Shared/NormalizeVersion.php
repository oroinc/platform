<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Version;

/**
 * Sets Data API version to "latest" if not specified otherwise.
 * Removes meaningless prefix, e.g. "v", from a version number.
 */
class NormalizeVersion implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $context->setVersion(Version::normalizeVersion($context->getVersion()));
    }
}
