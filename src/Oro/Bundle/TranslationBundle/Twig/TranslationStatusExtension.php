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
        $configData = $this->cm->get(TranslationStatusInterface::META_CONFIG_KEY);
        $stats      = $this->statisticProvider->get();

        if (!isset($configData[$languageCode])) {
            return false;
        }

        $installationDate = $this->getDateTimeFromString($configData[$languageCode]);
        $currentBuildDate = $this->getDateTimeFromString($stats[$languageCode]['lastBuildDate']);

        return $installationDate >= $currentBuildDate;
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
