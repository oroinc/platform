<?php

namespace Oro\Bundle\AddressBundle\Form\EventListener;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Matches submitted and existing items by index and set identifier value for submitted items.
 */
class ItemIdentifierCollectionTypeSubscriber implements EventSubscriberInterface
{
    /** @var string */
    private $idFieldName;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /**
     * @param string $idFieldName
     */
    public function __construct($idFieldName = 'id')
    {
        $this->idFieldName = $idFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        // this subscriber should be executed after CollectionTypeSubscriber
        return [
            FormEvents::PRE_SUBMIT  => ['preSubmit', -10]
        ];
    }

    /**
     * Remove empty items to prevent validation.
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $items = $event->getData();
        if (!is_array($items) || empty($items)) {
            return;
        }

        $existingItems = $event->getForm()->getData();
        if (!$existingItems instanceof Collection) {
            return;
        }

        if ($this->hasIdentifiers($items)) {
            return;
        }

        $index = 0;
        foreach ($items as &$item) {
            if (is_array($item) && isset($existingItems[$index])) {
                $existingItem = $existingItems[$index];
                if (is_object($existingItem)) {
                    $existingId = $this->getValue($existingItem, $this->idFieldName);
                    if (null !== $existingId) {
                        $item[$this->idFieldName] = $existingId;
                    }
                }
            }
            $index++;
        }
        unset($item);

        $event->setData($items);
    }

    /**
     * @param array $items
     *
     * @return bool
     */
    private function hasIdentifiers(array $items)
    {
        foreach ($items as $item) {
            if (is_array($item) && !empty($item[$this->idFieldName])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param object $item
     * @param string $fieldName
     *
     * @return mixed
     */
    private function getValue($item, $fieldName)
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = new PropertyAccessor();
        }

        return $this->propertyAccessor->getValue($item, $fieldName);
    }
}
