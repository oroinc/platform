<?php
namespace Oro\Bundle\AddressBundle\Form\EventListener;

use Oro\Bundle\AddressBundle\Entity\AbstractTypedAddress;
use Oro\Bundle\FormBundle\Entity\PrimaryItem;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * When address is created/updated from single form, it will ensure the rules of one primary address uniqueness
 */
class FixAddressesPrimarySubscriber implements EventSubscriberInterface
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
    protected $addressesAccess;

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

        $this->handlePrimary($address, $allAddresses);
    }

    /**
     * Only one address must be primary.
     *
     * @param PrimaryItem $address
     * @param PrimaryItem[] $allAddresses
     */
    protected function handlePrimary(PrimaryItem $address, $allAddresses)
    {
        if ($address->isPrimary()) {
            foreach ($allAddresses as $otherAddresses) {
                $otherAddresses->setPrimary(false);
            }
            $address->setPrimary(true);
        } elseif (count($allAddresses) == 1) {
            $address->setPrimary(true);
        }
    }
}
