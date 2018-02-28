<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Exception\TranslationProviderException;
use Oro\Bundle\TranslationBundle\Helper\LanguageHelper;

class ExternalTranslationsProvider
{
    /** @var TranslationServiceProvider */
    protected $serviceProvider;

    /** @var LanguageHelper */
    protected $languageHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * ExternalTranslationsProvider constructor.
     * @param TranslationServiceProvider $serviceProvider
     * @param LanguageHelper $languageHelper
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        TranslationServiceProvider $serviceProvider,
        LanguageHelper $languageHelper,
        DoctrineHelper $doctrineHelper
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->languageHelper = $languageHelper;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param Language $language
     *
     * @return bool
     *
     * @throws TranslationProviderException
     */
    public function updateTranslations(Language $language)
    {
        if ($this->hasTranslations($language)) {
            $updateFile = $this->downloadTranslations($language->getCode());
            $this->loadTranslations($updateFile, $language);

            return true;
        }

        return false;
    }

    /**
     * @param Language $language
     *
     * @return bool
     */
    public function hasTranslations(Language $language)
    {
        return $this->languageHelper->isTranslationsAvailable($language);
    }

    /**
     * @param string $languageCode
     *
     * @return string
     *
     * @throws TranslationProviderException
     */
    protected function downloadTranslations($languageCode)
    {
        $filePath = $this->languageHelper->downloadLanguageFile($languageCode);
        if (!$filePath) {
            throw new TranslationProviderException(sprintf('Unable to download translations for "%s"', $languageCode));
        }

        return $filePath;
    }

    /**
     * @param $updateFile
     * @param Language $language
     *
     * @throws TranslationProviderException
     */
    protected function loadTranslations($updateFile, Language $language)
    {
        $langCode = $language->getCode();

        if ($this->serviceProvider->loadTranslatesFromFile($updateFile, $langCode)) {
            $stats = $this->languageHelper->getLanguageStatistic($langCode);
            $language->setInstalledBuildDate($stats['lastBuildDate']);

            $this->getEntityManager()->flush($language);

            return;
        }

        throw new TranslationProviderException(sprintf(
            'Unable to load translations for "%s" from "%s"',
            $language->getCode(),
            $updateFile
        ));
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass(Language::class);
    }
}
