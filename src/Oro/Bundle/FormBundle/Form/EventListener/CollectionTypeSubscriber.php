<?php

namespace Oro\Bundle\FormBundle\Form\EventListener;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\FormBundle\Entity\EmptyItem;
use Oro\Bundle\FormBundle\Entity\PrimaryItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Removes empty items and sets an item as primary when the collection contains only one item.
 */
class CollectionTypeSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::SUBMIT => 'submit',
            FormEvents::PRE_SUBMIT  => 'preSubmit'
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function submit(FormEvent $event): void
    {
        $items = $event->getData();
        if ($items instanceof Collection) {
            foreach ($items as $item) {
                if ($item instanceof EmptyItem && $item->isEmpty()) {
                    $items->removeElement($item);
                }
            }
        } elseif (\is_array($items)) {
            $toRemoveKeys = [];
            foreach ($items as $key => $item) {
                if (!\is_array($item) && !$item) {
                    $toRemoveKeys[] = $key;
                }
            }
            foreach ($toRemoveKeys as $key) {
                unset($items[$key]);
            }
            $event->setData($items);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function preSubmit(FormEvent $event): void
    {
        $items = $event->getData();
        if (!$items || !\is_array($items)) {
            return;
        }

        if (!$this->hasPrimaryBehaviour($event)) {
            return;
        }

        $notEmptyItems = [];
        $hasPrimary = false;
        foreach ($items as $index => $item) {
            if (!$this->isArrayEmpty($item)) {
                $notEmptyItems[$index] = $item;
                if (!$hasPrimary && \array_key_exists('primary', $item) && $item['primary']) {
                    $hasPrimary = true;
                }
            }
        }
        if ($notEmptyItems && !$hasPrimary && \count($notEmptyItems) === 1) {
            $notEmptyItems[current(array_keys($notEmptyItems))]['primary'] = true;
        }
        $event->setData($notEmptyItems);
    }

    private function hasPrimaryBehaviour(FormEvent $event): bool
    {
        $form = $event->getForm();
        if (!$form->getConfig()->getOption('handle_primary')) {
            return false;
        }

        /** @var FormInterface $child */
        foreach ($form as $child) {
            $dataClass = $child->getConfig()->getDataClass();
            if ($dataClass && !is_subclass_of($dataClass, PrimaryItem::class)) {
                return false;
            }
        }

        return true;
    }

    private function isArrayEmpty(array $array): bool
    {
        foreach ($array as $val) {
            if (\is_array($val)) {
                if (!$this->isArrayEmpty($val)) {
                    return false;
                }
            } elseif (!empty($val)) {
                return false;
            }
        }
        return true;
    }
}
