<?php

namespace Oro\Bundle\TranslationBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\QueryAclHelper;
use Oro\Bundle\TranslationBundle\Api\Model\TranslationUpdate;
use Oro\Bundle\TranslationBundle\Api\TranslationIdUtil;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Loads translation data from the database into TranslationUpdate model.
 */
class LoadTranslationUpdate implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private QueryAclHelper $queryAclHelper;

    public function __construct(DoctrineHelper $doctrineHelper, QueryAclHelper $queryAclHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->queryAclHelper = $queryAclHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext|FormContext $context */

        if ($context->hasResult()) {
            // the entity is already loaded
            return;
        }

        $context->setResult(
            $this->loadTranslationModel($context->getId(), $context->getConfig(), $context->getRequestType())
        );
        // disable entity mapping
        $context->setEntityMapper(null);
    }

    private function loadTranslationModel(
        string $translationId,
        EntityDefinitionConfig $config,
        RequestType $requestType
    ): ?TranslationUpdate {
        $translationKeyId = TranslationIdUtil::extractTranslationKeyId($translationId);
        if (null === $translationKeyId) {
            return null;
        }
        $languageCode = TranslationIdUtil::extractLanguageCode($translationId);
        if (null === $languageCode) {
            return null;
        }

        $qb = $this->doctrineHelper->createQueryBuilder(TranslationKey::class, 'e')
            ->innerJoin(Language::class, 'language', Join::WITH, '1 = 1')
            ->leftJoin(
                Translation::class,
                'translation',
                Join::WITH,
                'translation.translationKey = e AND translation.language = language'
            )
            ->select('translation.id, translation.value')
            ->where('e.id = :id AND language.code = :langCode')
            ->setParameter('id', $translationKeyId)
            ->setParameter('langCode', $languageCode);

        $data = $this->queryAclHelper->protectQuery(clone $qb, $config, $requestType)->getArrayResult();
        if (!$data) {
            $notAclProtectedData = $qb->getQuery()->getArrayResult();
            if ($notAclProtectedData) {
                throw new AccessDeniedException('No access to the entity.');
            }

            return null;
        }

        return new TranslationUpdate(
            $translationId,
            $translationKeyId,
            $languageCode,
            $data[0]['id'],
            $data[0]['value']
        );
    }
}
