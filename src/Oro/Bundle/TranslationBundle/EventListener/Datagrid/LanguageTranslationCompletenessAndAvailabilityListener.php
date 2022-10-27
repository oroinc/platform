<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\EventListener\Datagrid;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\TranslationBundle\Download\TranslationMetricsProviderInterface;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;

/**
 * Adds info about availability of translation updates to the languages grid:
 * - calculates **translationCompleteness** as number of translated strings divided by number of all translation keys;
 * - sets **translationStatus** to one of the following values based on the availability of translations
 *      on the translation service:
 *      - **not_available** - if there are no available translations for this language on the translation service;
 *      - **up_to_date** - if the most recent translations version is already installed
 *      - **update_available** - if the installed translations version is older than the most recent available version
 *      - **install_available** - if the translations for this language are available but have not been installed yet
 */
class LanguageTranslationCompletenessAndAvailabilityListener
{
    private TranslationMetricsProviderInterface $translationMetricsProvider;
    private ManagerRegistry $doctrine;

    public function __construct(
        TranslationMetricsProviderInterface $translationMetricsProvider,
        ManagerRegistry $doctrine
    ) {
        $this->translationMetricsProvider = $translationMetricsProvider;
        $this->doctrine = $doctrine;
    }

    public function __invoke(OrmResultAfter $event): void
    {
        $translationKeyRepository = $this->doctrine->getRepository(TranslationKey::class);
        $translationKeyCount = $translationKeyRepository->getCount();

        $records = $event->getRecords();
        foreach ($records as $record) {
            $record->setValue(
                'translationCompleteness',
                $translationKeyCount ? $record->getValue('translationCount') / $translationKeyCount : null
            );
            $metrics = $this->translationMetricsProvider->getForLanguage($record->getValue('code'));
            $installedBuildDate = $record->getValue('installedBuildDate');
            if (null !== $metrics && !$record->getValue('localFilesLanguage')) {
                if (null === $installedBuildDate) {
                    $record->setValue('translationStatus', 'install_available');
                } elseif ($installedBuildDate < $metrics['lastBuildDate']) {
                    $record->setValue('translationStatus', 'update_available');
                } else {
                    $record->setValue('translationStatus', 'up_to_date');
                }
            } else {
                $record->setValue('translationStatus', 'not_available');
            }
        }
    }
}
