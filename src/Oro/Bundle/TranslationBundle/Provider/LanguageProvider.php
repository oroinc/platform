<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Intl\Intl;

class LanguageProvider
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var LocaleSettings */
    protected $localeSettings;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param ManagerRegistry $registry
     * @param LocaleSettings $localeSettings
     * @param AclHelper $aclHelper
     */
    public function __construct(ManagerRegistry $registry, LocaleSettings $localeSettings, AclHelper $aclHelper)
    {
        $this->registry = $registry;
        $this->localeSettings = $localeSettings;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param bool $onlyEnabled
     *
     * @return array
     */
    public function getAvailableLanguages($onlyEnabled = false)
    {
        $codes = $this->getRepository()->getAvailableLanguageCodes($onlyEnabled);
        $locales = Intl::getLocaleBundle()->getLocaleNames($this->localeSettings->getLanguage());

        return array_intersect_key($locales, array_flip($codes));
    }

    /**
     * @return array
     */
    public function getEnabledLanguages()
    {
        return $this->getRepository()->getAvailableLanguageCodes(true);
    }

    /**
     * @return array|Language[]
     */
    public function getAvailableLanguagesByCurrentUser()
    {
        return $this->getRepository()->getAvailableLanguagesByCurrentUser($this->aclHelper);
    }

    /**
     * @param bool $onlyEnabled
     *
     * @return array|Language[]
     */
    public function getLanguages($onlyEnabled = false)
    {
        return $this->getRepository()->getLanguages($onlyEnabled);
    }

    /**
     * @return null|object|Language
     */
    public function getDefaultLanguage()
    {
        return $this->getRepository()->findOneBy(['code' => Translator::DEFAULT_LOCALE]);
    }

    /**
     * @return LanguageRepository|ObjectRepository
     */
    protected function getRepository()
    {
        return $this->registry->getRepository(Language::class);
    }
}
