<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Processor\EnumOption;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Api\Model\TranslationCreate;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Binds new included translations to the enum option's translation key before their forms are submitted.
 * All translations share the same existing or newly created key.
 * The standard translation processor then saves them.
 */
class PrepareIncludedEnumOptionTranslations implements ProcessorInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext|FormContext $context */

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities || $includedEntities->isEmpty()) {
            return;
        }

        $requestData = $context->getRequestData();
        $key = ExtendHelper::buildEnumOptionTranslationKey((string)$context->getId());
        $translationKey = $this->findOrCreateTranslationKey($key);
        foreach ($includedEntities as $entity) {
            if (!$entity instanceof TranslationCreate) {
                continue;
            }
            if (TranslationKey::class !== $includedEntities->getClass($entity)) {
                continue;
            }

            $entityData = $includedEntities->getData($entity);
            $model = new TranslationCreate(
                TranslationManager::DEFAULT_DOMAIN,
                $key,
                $translationKey->getId() ?? 0,
                (string)($requestData['included'][$entityData->getIndex()]['attributes']['languageCode'] ?? '')
            );
            if (null === $translationKey->getId()) {
                $model->setNewTranslationKey($translationKey);
            }

            $entityId = $includedEntities->getId($entity);
            $includedEntities->remove(TranslationKey::class, $entityId);
            $includedEntities->add($model, TranslationKey::class, $entityId, $entityData);
        }
    }

    private function findOrCreateTranslationKey(string $key): TranslationKey
    {
        $translationKey = $this->doctrineHelper->getEntityRepositoryForClass(TranslationKey::class)->findOneBy([
            'key' => $key,
            'domain' => TranslationManager::DEFAULT_DOMAIN
        ]);
        if (!$translationKey) {
            $translationKey = new TranslationKey();
            $translationKey->setKey($key);
            $translationKey->setDomain(TranslationManager::DEFAULT_DOMAIN);
        }

        return $translationKey;
    }
}
