<?php

namespace Oro\Bundle\TranslationBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Helper\LanguageHelper;

class LanguageListener
{
    const STATS_COVERAGE_NAME = 'translationCompleteness';
    const STATS_COUNT = 'translationCount';
    const STATS_INSTALLED = 'translationInstalled';
    const STATS_AVAILABLE_UPDATE = 'translationAvailableUpdate';
    const STATS_AVAILABLE_INSTALL = 'translationAvailableInstall';

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
    public function __construct(
        LanguageHelper $languageHelper,
        DoctrineHelper $doctrineHelper
    ) {
        $this->languageHelper = $languageHelper;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $totalCount = $this->doctrineHelper->getEntityRepository(TranslationKey::class)->getCount();

        foreach ($records as $record) {
            /** @var Language $language */
            $language = $this->doctrineHelper->getEntity(Language::class, $record->getValue('id'));

            $record->setValue(
                self::STATS_COVERAGE_NAME,
                $totalCount ? $record->getValue(self::STATS_COUNT) / $totalCount : null
            );
            $record->setValue(self::STATS_INSTALLED, null !== $language->getInstalledBuildDate());
            $record->setValue(
                self::STATS_AVAILABLE_UPDATE,
                $this->languageHelper->isAvailableUpdateTranslates($language)
            );
            $record->setValue(
                self::STATS_AVAILABLE_INSTALL,
                $this->languageHelper->isAvailableInstallTranslates($language)
            );
        }
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $this->updateColumnsConfig($event->getConfig());
        $this->updateSourceConfig($event->getConfig());
    }

    /**
     * @param DatagridConfiguration $config
     */
    public function updateColumnsConfig(DatagridConfiguration $config)
    {
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

    /**
     * @param DatagridConfiguration $config
     */
    public function updateSourceConfig(DatagridConfiguration $config)
    {
        $query = $config->getOrmQuery();
        $query
            ->addSelect(sprintf('COUNT(translation) %s', self::STATS_COUNT))
            ->addLeftJoin(
                Translation::class,
                'translation',
                'WITH',
                sprintf('translation.language = %s', $query->getRootAlias())
            );
    }
}
