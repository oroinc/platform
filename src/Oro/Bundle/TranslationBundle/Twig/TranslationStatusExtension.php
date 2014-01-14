<?php

namespace Oro\Bundle\TranslationBundle\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider;
use Oro\Bundle\TranslationBundle\Translation\TranslationStatusInterface;

class TranslationStatusExtension extends \Twig_Extension
{
    /** @var ConfigManager */
    protected $cm;

    /** @var TranslationStatisticProvider */
    protected $statisticProvider;

    /** @var array */
    protected $processedLanguages = [];

    /**
     * @param ConfigManager                $cm
     * @param TranslationStatisticProvider $statisticProvider
     */
    public function __construct(ConfigManager $cm, TranslationStatisticProvider $statisticProvider)
    {
        $this->cm                = $cm;
        $this->statisticProvider = $statisticProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_translation_translation_status';
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_translation_is_fresh', [$this, 'isFresh'])
        ];
    }

    /**
     * Check whenever given language package up to date
     *
     * @param string $languageCode
     *
     * @return bool
     */
    public function isFresh($languageCode)
    {
        if (!isset($this->processedLanguages[$languageCode])) {
            $configData = $this->cm->get(TranslationStatusInterface::META_CONFIG_KEY);
            $stats      = $this->statisticProvider->get();

            if (isset($configData[$languageCode])) {
                $stats = array_filter(
                    $stats,
                    function ($langInfo) use ($languageCode) {
                        return $langInfo['code'] === $languageCode;
                    }
                );
                $lang  = array_pop($stats);

                if ($lang) {
                    $installationDate = $this->getDateTimeFromString($configData[$languageCode]['lastBuildDate']);
                    $currentBuildDate = $this->getDateTimeFromString($lang['lastBuildDate']);

                    $this->processedLanguages[$languageCode] = $installationDate >= $currentBuildDate;
                } else {
                    // could not retrieve current language stats, so assume that it's fresh
                    $this->processedLanguages[$languageCode] = true;
                }
            } else {
                // if we do not have information about installed time then assume that needs update
                $this->processedLanguages[$languageCode] = false;
            }

        }

        return $this->processedLanguages[$languageCode];
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
