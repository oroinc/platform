<?php

namespace Oro\Bundle\TestFrameworkBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\Repository\ItemRepository;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

class WebsiteSearchItemIndexerListener
{
    /**
     * @var ItemRepository
     */
    private $itemRepository;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper, LocalizationHelper $localizationHelper)
    {
        $this->itemRepository = $doctrineHelper->getEntityRepositoryForClass(Item::class);
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param IndexEntityEvent $event
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $entityClass = $event->getEntityClass();

        if ($entityClass !== Item::class) {
            return;
        }

        $items = $this->itemRepository->getItemsByIds($event->getEntityIds());

        $localizations = $this->localizationHelper->getLocalizations();

        foreach ($items as $item) {
            // Non localized fields
            $event->addField(
                $item->getId(),
                Query::TYPE_INTEGER,
                'integerValue',
                $item->integerValue
            );
            $event->addField(
                $item->getId(),
                Query::TYPE_DECIMAL,
                'decimalValue',
                $item->decimalValue
            );
            $event->addField(
                $item->getId(),
                Query::TYPE_DECIMAL,
                'floatValue',
                $item->floatValue
            );
            $event->addField(
                $item->getId(),
                Query::TYPE_DATETIME,
                'datetimeValue',
                $item->datetimeValue
            );

            // Localized fields
            foreach ($localizations as $localization) {
                $localizedFields = [
                    'stringValue' => $item->stringValue,
                ];

                foreach ($localizedFields as $fieldName => $fieldValue) {
                    $event->addField(
                        $item->getId(),
                        Query::TYPE_TEXT,
                        sprintf('%s_%s', $fieldName, $localization->getId()),
                        $fieldValue
                    );
                }

                // All text field
                $event->addField(
                    $item->getId(),
                    Query::TYPE_TEXT,
                    sprintf('all_text_%s', $localization->getId()),
                    implode(' ', $localizedFields)
                );
            }

            $event->addField(
                $item->getId(),
                Query::TYPE_TEXT,
                'phone',
                $item->phone
            );
            $event->addField(
                $item->getId(),
                Query::TYPE_TEXT,
                'blobValue',
                (string)$item->blobValue
            );
        }
    }
}
