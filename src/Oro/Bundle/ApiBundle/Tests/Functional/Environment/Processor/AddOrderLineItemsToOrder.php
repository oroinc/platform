<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOrder;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOrderLineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Ensures that the processing order contains all included order line items
 * assigned to this order.
 * This processor is required because TestOrderLineItem::setOrder
 * does not add the order line item to the order, as result the response
 * of the create order action does not contains included order line items.
 */
class AddOrderLineItemsToOrder implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        /** @var TestOrder|null $order */
        $order = $context->getResult();
        if (null === $order) {
            return;
        }

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            return;
        }

        foreach ($includedEntities as $entity) {
            if ($entity instanceof TestOrderLineItem
                && $entity->getOrder() === $order
                && !$order->getLineItems()->contains($entity)
            ) {
                $order->getLineItems()->add($entity);
            }
        }
    }
}
