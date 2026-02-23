<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener\Cache;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Abstract class for listeners which will invalidate a enum translations cache
 */
abstract class AbstractEnumOptionListener
{
    private const string TRANSLATION_DOMAIN = 'messages';

    private array $entityToUpdateTranslations = [];
    private array $entityToRemoveTranslations = [];

    public function __construct(
        protected ManagerRegistry $doctrine,
        protected EnumTranslationCache $enumTranslationCache,
        protected TranslationManager $translationManager,
        protected ConfigTranslationHelper $translationHelper,
        protected MessageProducerInterface $messageProducer
    ) {
    }

    public function postPersist(object $entity): void
    {
        $this->invalidateCache($entity);
    }

    public function postUpdate(object $entity): void
    {
        $this->setEntityToUpdateTranslation($entity);
        $this->invalidateCache($entity);
    }

    public function postRemove(object $entity): void
    {
        $this->setEntityToRemoveTranslation($entity);
        $this->invalidateCache($entity);
    }

    public function setEntityToRemoveTranslation(object $entity): void
    {
        $this->entityToRemoveTranslations[] = $entity;
    }


    public function setEntityToUpdateTranslation(object $entity): void
    {
        $this->entityToUpdateTranslations[] = $entity;
    }

    public function postFlush(): void
    {
        if ([] === $this->entityToUpdateTranslations && [] === $this->entityToRemoveTranslations) {
            return;
        }
        foreach ($this->entityToRemoveTranslations as $entity) {
            $this->deleteEnumOptionTranslation($entity);
        }
        foreach ($this->entityToUpdateTranslations as $entity) {
            $this->updateEnumOptionTranslation($entity);
        }

        $this->entityToUpdateTranslations = [];
        $this->entityToRemoveTranslations = [];
        $this->translationManager->flush();
    }

    protected function updateEnumOptionTranslation(object $entity, bool $flush = false): void
    {
        list($id, $name, $locale) = $this->getEntityTranslationInfo($entity);
        if (null === $id) {
            return;
        }
        $locale = $locale ?? $this->translationHelper->getLocale();
        $this->translationManager->saveTranslation(
            ExtendHelper::buildEnumOptionTranslationKey($id),
            $name,
            $locale,
            self::TRANSLATION_DOMAIN,
            Translation::SCOPE_UI
        );
        $this->translationManager->invalidateCache($locale);
        if ($flush) {
            $this->translationManager->flush();
        }
    }

    protected function deleteEnumOptionTranslation(object $entity): void
    {
        list($id, $name, $locale) = $this->getEntityTranslationInfo($entity);
        if (null === $id) {
            return;
        }
        $locale = $locale ?? $this->translationHelper->getLocale();
        $key = ExtendHelper::buildEnumOptionTranslationKey($id);
        $entityManager = $this->getEntityManager(Translation::class);

        $translation = $entityManager->getRepository(Translation::class)
            ->findTranslation($key, $locale, self::TRANSLATION_DOMAIN);
        if (null !== $translation) {
            $entityManager->remove($translation);
        }
        $this->translationManager->removeTranslationKey($key, self::TRANSLATION_DOMAIN);
        $this->translationManager->invalidateCache($locale);
    }

    abstract protected function invalidateCache(object $entity);

    abstract protected function getEntityTranslationInfo(object $entity): array;

    private function getEntityManager(string $entityClass): EntityManager
    {
        return $this->doctrine->getManagerForClass($entityClass);
    }
}
