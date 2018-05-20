<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOrderLineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Ensures that the processing order line item is contained in an included order
 * to that the order line item is assigned to.
 * This processor is required because TestOrderLineItem::setOrder
 * does not add the order line item to the order, as result the response
 * of the create order line item action does not contains this order line item in the included order.
 */
class AddOrderLineItemToOrder implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        /** @var TestOrderLineItem|null $lineItem */
        $lineItem = $context->getResult();
        if (null === $lineItem) {
            return;
        }

        $order = $lineItem->getOrder();
        if (null === $order) {
            return;
        }

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            return;
        }

        foreach ($includedEntities as $entity) {
            if ($entity === $order && !$order->getLineItems()->contains($lineItem)) {
                $order->getLineItems()->add($lineItem);
            }
        }
    }
}
