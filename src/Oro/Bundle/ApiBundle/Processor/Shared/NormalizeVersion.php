<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Version;

class NormalizeVersion implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $version = $context->getVersion();
        if (null === $version) {
            $context->setVersion(Version::LATEST);
        } elseif (0 === strpos($version, 'v')) {
            $context->setVersion(substr($version, 1));
        }
    }
}
