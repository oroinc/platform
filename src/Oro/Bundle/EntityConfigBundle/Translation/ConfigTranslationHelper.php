<?php

namespace Oro\Bundle\EntityConfigBundle\Translation;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class ConfigTranslationHelper
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DynamicTranslationMetadataCache */
    protected $translationCache;

    /**
     * @param ManagerRegistry $registry
     * @param TranslatorInterface $translator
     * @param DynamicTranslationMetadataCache $translationCache
     */
    public function __construct(
        ManagerRegistry $registry,
        TranslatorInterface $translator,
        DynamicTranslationMetadataCache $translationCache
    ) {
        $this->registry = $registry;
        $this->translator = $translator;
        $this->translationCache = $translationCache;
    }

    /**
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function isTranslationEqual($key, $value)
    {
        return $value === $this->translator->trans($key);
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->translator->getLocale();
    }

    /**
     * @param array $translations
     */
    public function saveTranslations(array $translations)
    {
        if (!$translations) {
            return;
        }

        $locale = $this->translator->getLocale();
        $entities = [];

        foreach ($translations as $key => $value) {
            $entities[] = $this->createTranslationEntity($key, $value, $locale);
        }

        // mark translation cache dirty
        $this->translationCache->updateTimestamp($locale);

        $this->getTranslationManager()->flush($entities);
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $locale
     * @return Translation
     */
    protected function createTranslationEntity($key, $value, $locale)
    {
        return $this->getTranslationRepository()->saveValue(
            $key,
            $value,
            $locale,
            TranslationRepository::DEFAULT_DOMAIN,
            Translation::SCOPE_UI
        );
    }

    /**
     * @return EntityManager
     */
    protected function getTranslationManager()
    {
        return $this->registry->getManagerForClass(Translation::ENTITY_NAME);
    }

    /**
     * @return TranslationRepository
     */
    protected function getTranslationRepository()
    {
        return $this->getTranslationManager()->getRepository(Translation::ENTITY_NAME);
    }
}
