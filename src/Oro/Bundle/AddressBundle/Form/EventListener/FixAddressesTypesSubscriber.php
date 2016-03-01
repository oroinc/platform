<?php
namespace Oro\Bundle\AddressBundle\Form\EventListener;

use Oro\Bundle\AddressBundle\Entity\AbstractTypedAddress;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * When address is created/updated from single form, it will ensure the rules of one address types
 * uniqueness
 */
class FixAddressesTypesSubscriber implements EventSubscriberInterface
{
    /**
     * Property path to collection of all addresses (e.g. 'owner.address' means $address->getOwner()->getAddresses())
     *
     * @var string
     */
    protected $addressesProperty;

    /**
     * @var PropertyAccess
     */
    protected $addressAccess;

    /**
     * @param string $addressesProperty Address property path like "owner.addresses"
     */
    public function __construct($addressesProperty)
    {
        $this->addressesAccess = PropertyAccess::createPropertyAccessor();
        $this->addressesProperty = $addressesProperty;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::POST_SUBMIT => 'postSubmit'
        );
    }

    /**
     * Removes empty collection elements.
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var AbstractTypedAddress $address */
        $address = $event->getData();

        /** @var AbstractTypedAddress[] $allAddresses */
        $allAddresses = $this->addressesAccess->getValue($address, $this->addressesProperty);

        $this->handleType($address, $allAddresses);
    }

    /**
     * Two addresses must not have same types
     *
     * @param AbstractTypedAddress $address
     * @param AbstractTypedAddress[] $allAddresses
     */
    protected function handleType(AbstractTypedAddress $address, $allAddresses)
    {
        $types = $address->getTypes()->toArray();
        if (count($types)) {
            foreach ($allAddresses as $otherAddresses) {
                foreach ($types as $type) {
                    $otherAddresses->removeType($type);
                }
            }
            foreach ($types as $type) {
                $address->addType($type);
            }
        }
    }
}
