<?php

namespace Oro\Bundle\TranslationBundle\Helper;

use Oro\Bundle\TranslationBundle\Entity\Language;

use Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider;

class LanguageHelper
{
    /** @var TranslationStatisticProvider */
    protected $translationStatisticProvider;

    /**
     * @param TranslationStatisticProvider $translationStatisticProvider
     */
    public function __construct(TranslationStatisticProvider $translationStatisticProvider)
    {
        $this->translationStatisticProvider = $translationStatisticProvider;
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

        // TODO: should be fixed in https://magecore.atlassian.net/browse/BAP-10608
        $stats['en'] = [
            'translationStatus' => 100,
        ];

        return isset($stats[$language->getCode()]) ? (int)$stats[$language->getCode()]['translationStatus'] : null;
    }

    /**
     * @return type
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
}
