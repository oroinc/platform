<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Intl\Intl;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;

class LanguageProvider
{
    /** @var ObjectRepository|LanguageRepository */
    protected $repository;

    /** @var LocaleSettings */
    protected $localeSettings;

    /**
     * @param ObjectRepository $repository
     * @param LocaleSettings $localeSettings
     */
    public function __construct(ObjectRepository $repository, LocaleSettings $localeSettings)
    {
        $this->repository = $repository;
        $this->localeSettings = $localeSettings;
    }

    /**
     * @return array
     */
    public function getAvailableLanguages()
    {
        $codes = $this->repository->getAvailableLanguageCodes();
        $locales = Intl::getLocaleBundle()->getLocaleNames($this->localeSettings->getLanguage());

        return array_intersect_key($locales, array_flip($codes));
    }

    /**
     * @return array
     */
    public function getEnabledLanguages()
    {
        return $this->repository->getAvailableLanguageCodes(true);
    }
}
