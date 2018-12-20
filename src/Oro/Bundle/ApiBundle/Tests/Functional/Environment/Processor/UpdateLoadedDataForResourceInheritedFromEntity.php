<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates "name" field of "Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestOverrideClassTargetModel".
 * Also updates "name" field of "Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestTarget"
 * This example of loaded data customization is used to test "override_class" option.
 */
class UpdateLoadedDataForResourceInheritedFromEntity implements ProcessorInterface
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

        $data['name'] .= ' (customized)';
        $context->setResult($data);
    }
}
