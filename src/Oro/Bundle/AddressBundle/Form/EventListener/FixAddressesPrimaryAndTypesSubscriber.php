<?php
namespace Oro\Bundle\AddressBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * When address is created/updated from single form, it will ensure the rules of one primary address and address types
 * uniqueness
 *
 * @deprecated since 1.8. Use FixAddressesPrimarySubscriber and FixAddressesTypesSubscriber instead.
 */
class FixAddressesPrimaryAndTypesSubscriber implements EventSubscriberInterface
{
    /** @var FixAddressesPrimarySubscriber  */
    protected $fixAddressesPrimarySubscriber;

    /** @var FixAddressesTypesSubscriber  */
    protected $fixAddressesTypesSubscriber;

    /**
     * @param string $addressesProperty Address property path like "owner.addresses"
     */
    public function __construct($addressesProperty)
    {
        $this->fixAddressesPrimarySubscriber = new FixAddressesPrimarySubscriber($addressesProperty);
        $this->fixAddressesTypesSubscriber = new FixAddressesTypesSubscriber($addressesProperty);
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
        $this->fixAddressesPrimarySubscriber->postSubmit($event);
        $this->fixAddressesTypesSubscriber->postSubmit($event);
    }
}
