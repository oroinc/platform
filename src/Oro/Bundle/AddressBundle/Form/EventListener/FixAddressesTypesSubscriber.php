<?php

namespace Oro\Bundle\AddressBundle\Form\EventListener;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AddressBundle\Entity\AbstractTypedAddress;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Ensures that there is no several addresses with the same type.
 */
class FixAddressesTypesSubscriber implements EventSubscriberInterface
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
     * @param string                         $addressesPropertyPath
     * @param PropertyAccessorInterface|null $propertyAccessor
     */
    public function __construct(string $addressesPropertyPath, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->addressesPropertyPath = $addressesPropertyPath;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT => 'postSubmit'
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var AbstractTypedAddress $address */
        $address = $event->getData();
        $allAddresses = $this->getAllAddresses($address);
        if (null === $allAddresses) {
            return;
        }

        $types = $address->getTypes()->toArray();
        if (count($types) > 0) {
            foreach ($allAddresses as $otherAddress) {
                foreach ($types as $type) {
                    $otherAddress->removeType($type);
                }
            }
            foreach ($types as $type) {
                $address->addType($type);
            }
        }
    }

    /**
     * @param AbstractTypedAddress $address
     *
     * @return AbstractTypedAddress[]|Collection|null
     */
    private function getAllAddresses(AbstractTypedAddress $address)
    {
        $path = explode('.', $this->addressesPropertyPath);
        $addressesField = array_pop($path);
        if (count($path) === 0) {
            throw new InvalidPropertyPathException(sprintf(
                'The addresses property path "%s" must have at least 2 elements.',
                $this->addressesPropertyPath
            ));
        }
        $addressesOwner = $address;
        foreach ($path as $fieldName) {
            $addressesOwner = $this->propertyAccessor->getValue($addressesOwner, $fieldName);
            if (null === $addressesOwner) {
                break;
            }
        }
        if (null === $addressesOwner) {
            return null;
        }

        return $this->propertyAccessor->getValue($addressesOwner, $addressesField);
    }
}
