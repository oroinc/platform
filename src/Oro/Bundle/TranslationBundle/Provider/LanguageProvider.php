<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Intl\Intl;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;

class LanguageProvider
{
    /** @var ObjectRepository|LanguageRepository */
    protected $repository;

    /** @var LocaleSettings */
    protected $localeSettings;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param ObjectRepository $repository
     * @param LocaleSettings $localeSettings
     * @param AclHelper $aclHelper
     */
    public function __construct(ObjectRepository $repository, LocaleSettings $localeSettings, AclHelper $aclHelper)
    {
        $this->repository = $repository;
        $this->localeSettings = $localeSettings;
        $this->aclHelper = $aclHelper;
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

    /**
     * @return array|Language[]
     */
    public function getAvailableLanguagesByCurrentUser()
    {
        return $this->repository->getAvailableLanguagesByCurrentUser($this->aclHelper);
    }
}
