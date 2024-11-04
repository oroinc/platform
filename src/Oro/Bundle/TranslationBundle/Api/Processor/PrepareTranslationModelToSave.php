<?php

namespace Oro\Bundle\TranslationBundle\Api\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\TranslationBundle\Api\Model\TranslationModel;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Prepares the TranslationModel model to save into the database.
 */
class PrepareTranslationModelToSave implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var TranslationModel $model */
        $model = $context->getData();
        $translatedValue = $model->getTranslatedValue();
        $translationEntity = $this->loadTranslationEntity($model->getTranslationEntityId());
        if (null === $translatedValue) {
            // it was requested to delete the translation
            if (null === $translationEntity) {
                // nothing to do because the translation does not exist
                $context->setData(null);
            } else {
                $context->addAdditionalEntityToRemove($translationEntity);
                $context->setData($translationEntity->getTranslationKey());
            }
        } else {
            // it was requested to update the translation
            if (null === $translationEntity) {
                $translationEntity = new Translation();
                $translationEntity->setScope(Translation::SCOPE_UI);
                $translationEntity->setTranslationKey($this->loadTranslationKeyEntity($model->getTranslationKeyId()));
                $translationEntity->setLanguage($this->loadLanguageEntity($model->getLanguageCode()));
            }
            $translationEntity->setValue($translatedValue);
            $context->addAdditionalEntity($translationEntity);
            $context->setData($translationEntity->getTranslationKey());
        }
    }

    private function loadTranslationEntity(?int $translationEntityId): ?Translation
    {
        if (null === $translationEntityId) {
            return null;
        }

        return  $this->doctrineHelper->createQueryBuilder(Translation::class, 't')
            ->innerJoin('t.translationKey', 'tk')
            ->addSelect('tk')
            ->where('t.id = :id')
            ->setParameter('id', $translationEntityId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function loadTranslationKeyEntity(?int $translationKeyId): TranslationKey
    {
        return  $this->getEntityManager()->find(TranslationKey::class, $translationKeyId);
    }

    private function loadLanguageEntity(string $languageCode): Language
    {
        return  $this->doctrineHelper->createQueryBuilder(Language::class, 'l')
            ->where('l.code = :code')
            ->setParameter('code', $languageCode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrineHelper->getEntityManagerForClass(Translation::class);
    }
}
