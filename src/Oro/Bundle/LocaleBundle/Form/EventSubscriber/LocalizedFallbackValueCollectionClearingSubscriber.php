<?php

namespace Oro\Bundle\LocaleBundle\Form\EventSubscriber;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Form subscriber designed for usage on {@see LocalizedFallbackValueCollectionType} to make it empty after submit
 * if it was empty before or if there were no changes made since the initial data was set.
 *
 * The use case is the following: prevent the persistence of the localized fallback values collection to a database
 * if it is initially proxied from another entity and is not changed after submit.
 */
class LocalizedFallbackValueCollectionClearingSubscriber implements EventSubscriberInterface
{
    /**
     * @var array<string,array<?int,AbstractLocalizedFallbackValue>> Snapshots of localized fallback value collections.
     *  [
     *      string $formHash => [
     *          ?int $localization => AbstractLocalizedFallbackValue,
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    private array $snapshots = [];

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            FormEvents::SUBMIT => 'onSubmit',
        ];
    }

    public function onPreSetData(PreSetDataEvent $event): void
    {
        /** @var Collection<AbstractLocalizedFallbackValue> $collection */
        $collection = $event->getData();
        if (!$collection instanceof Collection) {
            return;
        }

        if ($collection instanceof PersistentCollection && count($collection->getSnapshot())) {
            // Skips taking snapshot if persistent collection is originally not empty.
            return;
        }

        $hash = spl_object_hash($event->getForm());

        foreach ($collection as $localizedFallbackValue) {
            $localization = $localizedFallbackValue->getLocalization();
            $this->snapshots[$hash][$localization?->getId()] = clone $localizedFallbackValue;
        }
    }

    public function onSubmit(SubmitEvent $event): void
    {
        /** @var Collection<AbstractLocalizedFallbackValue> $collection */
        $collection = $event->getData();
        if (!$collection instanceof Collection) {
            return;
        }

        $hash = spl_object_hash($event->getForm());
        $snapshot = $this->snapshots[$hash] ?? null;
        if (!$snapshot) {
            // Skips checking for changes because there is no snapshot to compare with.
            return;
        }

        $isChanged = false;
        foreach ($collection as $localizedFallbackValue) {
            $snapshotValue = $snapshot[$localizedFallbackValue->getLocalization()?->getId()] ?? null;
            if ($snapshotValue) {
                // Non-strict comparison is used on purpose.
                if ($snapshotValue != $localizedFallbackValue) {
                    $isChanged = true;
                    break;
                }
            } elseif ($localizedFallbackValue->getFallback() !== FallbackType::SYSTEM) {
                $isChanged = true;
                break;
            }
        }

        if (!$isChanged) {
            // Clears the collection because there are no changes since initial data was set.
            $collection->clear();
            unset($this->snapshots[$hash]);
        }
    }
}
