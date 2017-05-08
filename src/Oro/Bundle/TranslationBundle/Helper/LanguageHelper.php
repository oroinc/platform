<?php

namespace Oro\Bundle\TranslationBundle\Helper;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Provider\OroTranslationAdapter;
use Oro\Bundle\TranslationBundle\Provider\PackagesProvider;
use Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider;
use Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider;

class LanguageHelper
{
    /** @var TranslationStatisticProvider */
    protected $translationStatisticProvider;

    /** @var PackagesProvider */
    protected $packagesProvider;

    /** @var OroTranslationAdapter */
    protected $translationAdapter;

    /** @var TranslationServiceProvider */
    protected $translationServiceProvider;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param TranslationStatisticProvider $translationStatisticProvider
     * @param PackagesProvider $packagesProvider
     * @param OroTranslationAdapter $translationAdapter
     * @param TranslationServiceProvider $translationServiceProvider
     * @param ConfigManager $configManager
     */
    public function __construct(
        TranslationStatisticProvider $translationStatisticProvider,
        PackagesProvider $packagesProvider,
        OroTranslationAdapter $translationAdapter,
        TranslationServiceProvider $translationServiceProvider,
        ConfigManager $configManager
    ) {
        $this->translationStatisticProvider = $translationStatisticProvider;
        $this->packagesProvider = $packagesProvider;
        $this->translationAdapter = $translationAdapter;
        $this->translationServiceProvider = $translationServiceProvider;
        $this->configManager = $configManager;
    }

    /**
     * @param Language $language
     *
     * @return bool
     */
    public function isTranslationsAvailable(Language $language)
    {
        return $this->isAvailableInstallTranslates($language) || $this->isAvailableUpdateTranslates($language);
    }

    /**
     * @param Language $language
     * @return bool
     */
    public function isAvailableInstallTranslates(Language $language)
    {
        if (null === $language->getCode() || null !== $language->getInstalledBuildDate()) {
            return false;
        }

        $stats = $this->getStatistic();

        return isset($stats[$language->getCode()]);
    }

    /**
     * @param Language $language
     * @return bool
     */
    public function isAvailableUpdateTranslates(Language $language)
    {
        if (null === $language->getCode() || null === $language->getInstalledBuildDate()) {
            return false;
        }

        $stats = $this->getStatistic();

        if (!isset($stats[$language->getCode()])) {
            return false;
        }

        $lastBuildDate = $this->getDateTimeFromString($stats[$language->getCode()]['lastBuildDate']);

        return $language->getInstalledBuildDate() < $lastBuildDate;
    }

    /**
     * @param Language $language
     * @return int
     */
    public function getTranslationStatus(Language $language)
    {
        $stats = $this->getStatistic();

        return isset($stats[$language->getCode()]) ? (int)$stats[$language->getCode()]['translationStatus'] : null;
    }

    /**
     * @param string $code
     * @return array
     */
    public function getLanguageStatistic($code)
    {
        $stats = $this->getStatistic();

        $languageStat = isset($stats[$code]) ? $stats[$code] : null;

        if ($languageStat) {
            $languageStat['lastBuildDate'] = $this->getDateTimeFromString($languageStat['lastBuildDate']);
        }

        return $languageStat;
    }

    /**
     * @param string $code
     * @return null|string
     */
    public function downloadLanguageFile($code)
    {
        $projects = $this->packagesProvider->getInstalledPackages();
        $this->translationServiceProvider->setAdapter($this->translationAdapter);

        $pathToSave = $this->translationServiceProvider->getTmpDir('download_' . $code);

        $isDownloaded = $this->translationServiceProvider->download($pathToSave, $projects, $code);

        return $isDownloaded ? $pathToSave : null;
    }

    /**
     * @return array
     */
    protected function getStatistic()
    {
        $stats = [];
        foreach ($this->translationStatisticProvider->get() as $info) {
            $stats[$info['code']] = $info;
        }

        return $stats;
    }

    /**
     * @param string $stringDate
     *
     * @return \DateTime
     */
    protected function getDateTimeFromString($stringDate)
    {
        $date = strtotime($stringDate);

        $defaultTimezone = date_default_timezone_get();

        date_default_timezone_set('UTC');

        $result = new \DateTime();
        $result->setTimestamp($date);

        date_default_timezone_set($defaultTimezone);

        return $result;
    }

    /**
     * @param Language $language
     *
     * @return bool
     */
    public function isDefaultLanguage(Language $language)
    {
        $defaultLanguage = $this->configManager->get(Configuration::getConfigKeyByName(Configuration::LANGUAGE));

        return $defaultLanguage === $language->getCode();
    }
}
