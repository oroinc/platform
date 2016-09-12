<?php

namespace Oro\Bundle\TranslationBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\GridResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Helper\LanguageHelper;

class LanguageListener
{
    const STATS_COVERAGE_NAME = 'translationCompleteness';
    const STATS_INSTALLED = 'translationInstalled';
    const STATS_AVAILABLE_UPDATE = 'translationAvailableUpdate';

    const COLUMN_STATUS = 'translationStatus';
    const COLUMN_COVERAGE = 'translationCompleteness';

    /** @var LanguageHelper */
    protected $languageHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param LanguageHelper $languageHelper
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(LanguageHelper $languageHelper, DoctrineHelper $doctrineHelper)
    {
        $this->languageHelper = $languageHelper;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param GridResultAfter $event
     */
    public function onResultAfter(GridResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        foreach ($records as $record) {
            /** @var Language $language */
            $language = $this->doctrineHelper->getEntity(Language::class, $record->getValue('id'));

            $record->setValue(self::STATS_COVERAGE_NAME, $this->languageHelper->getTranslationStatus($language));
            $record->setValue(self::STATS_INSTALLED, null !== $language->getInstalledBuildDate());
            $record->setValue(
                self::STATS_AVAILABLE_UPDATE,
                $this->languageHelper->isAvailableUpdateTranslates($language)
            );
        }
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        $columns = $config->offsetGetByPath('[columns]', []);

        $columns[self::COLUMN_COVERAGE] = array_merge(
            [
                'label' => 'oro.translation.language.translation_completeness.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'template' => 'OroTranslationBundle:Language:Datagrid/translationCompleteness.html.twig',
            ],
            isset($columns[self::COLUMN_COVERAGE]) ? $columns[self::COLUMN_COVERAGE] : []
        );

        $columns[self::COLUMN_STATUS] = array_merge(
            [
                'label' => 'oro.translation.language.translation_status.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'template' => 'OroTranslationBundle:Language:Datagrid/translationStatus.html.twig',
            ],
            isset($columns[self::COLUMN_STATUS]) ? $columns[self::COLUMN_STATUS] : []
        );

        $config->offsetSetByPath('[columns]', $columns);
    }
}
