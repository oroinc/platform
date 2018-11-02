<?php

namespace Oro\Bundle\AddressBundle\Api\Processor;

use Oro\Bundle\AddressBundle\Form\EventListener\FixAddressesPrimarySubscriber;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Ensures that a primary address exists and it is only one.
 */
class FixPrimaryAddresses implements ProcessorInterface
{
    /**
     * The property path to collection of all addresses
     * (e.g. "owner.addresses" means $address->getOwner()->getAddresses())
     *
     * @var string
     */
    private $addressesPropertyPath;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /**
     * @param string                    $addressesPropertyPath
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(string $addressesPropertyPath, PropertyAccessorInterface $propertyAccessor)
    {
        $this->addressesPropertyPath = $addressesPropertyPath;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        $handler = new FixAddressesPrimarySubscriber($this->addressesPropertyPath, $this->propertyAccessor);
        $handler->postSubmit(new FormEvent($context->getForm(), $context->getData()));
    }
}
