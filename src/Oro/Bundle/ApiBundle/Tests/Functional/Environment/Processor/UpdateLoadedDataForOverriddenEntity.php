<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates "name" field of "Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOverrideClassTarget" that is
 * the parent for "Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestOverrideClassTargetModel".
 * This example of loaded data customization is used to test "override_class" option.
 */
class UpdateLoadedDataForOverriddenEntity implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data) || !array_key_exists('name', $data)) {
            return;
        }

        $data['name'] .= ' (customized by parent)';
        $context->setResult($data);
    }
}
