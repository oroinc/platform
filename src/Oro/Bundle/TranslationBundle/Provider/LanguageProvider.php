<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Translation\Translator;

/**
 * Provides various utility methods to get information about installed languages.
 */
class LanguageProvider
{
    private ManagerRegistry $registry;
    protected LocaleSettings $localeSettings;
    protected AclHelper $aclHelper;

    public function __construct(ManagerRegistry $registry, LocaleSettings $localeSettings, AclHelper $aclHelper)
    {
        $this->registry = $registry;
        $this->localeSettings = $localeSettings;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @return string[]
     */
    public function getAvailableLanguageCodes(bool $onlyEnabled = false): array
    {
        return \array_keys($this->getRepository()->getAvailableLanguageCodesAsArrayKeys($onlyEnabled));
    }

    /**
     * @return Language[]
     */
    public function getAvailableLanguagesByCurrentUser(): array
    {
        return $this->getRepository()->getAvailableLanguagesByCurrentUser($this->aclHelper);
    }

    /**
     * @return Language[]
     */
    public function getLanguages(bool $onlyEnabled = false): array
    {
        return $this->getRepository()->getLanguages($onlyEnabled);
    }

    public function getDefaultLanguage(): ?Language
    {
        return $this->getRepository()->findOneBy(['code' => Translator::DEFAULT_LOCALE]);
    }

    private function getRepository(): LanguageRepository
    {
        return $this->registry->getRepository(Language::class);
    }
}
