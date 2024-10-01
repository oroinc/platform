<?php

namespace Oro\Bundle\TranslationBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\QueryAclHelper;
use Oro\Bundle\TranslationBundle\Api\Model\TranslationCreate;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Loads translation data from the database into TranslationCreate model.
 */
class LoadTranslationCreate implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private QueryAclHelper $queryAclHelper;

    public function __construct(DoctrineHelper $doctrineHelper, QueryAclHelper $queryAclHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->queryAclHelper = $queryAclHelper;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext|FormContext $context */

        if ($context->hasResult()) {
            // the entity is already loaded
            return;
        }

        $model = $this->loadTranslationModel(
            $context->getRequestData(),
            $context->getConfig(),
            $context->getRequestType()
        );
        if (null === $model) {
            throw new NotFoundHttpException('An entity does not exist.');
        }

        $context->setResult($model);
        // disable entity mapping
        $context->setEntityMapper(null);
    }

    private function loadTranslationModel(
        array $requestData,
        EntityDefinitionConfig $config,
        RequestType $requestType
    ): ?TranslationCreate {
        $domain = $requestData['domain'] ?? null;
        $key = $requestData['key'] ?? null;
        $languageCode = $requestData['languageCode'] ?? null;
        if (!$domain || !$key || !$languageCode) {
            return new TranslationCreate($domain ?? '', $key ?? '', 0, $languageCode ?? '');
        }

        $qb = $this->doctrineHelper->createQueryBuilder(TranslationKey::class, 'e')
            ->innerJoin(Language::class, 'language', Join::WITH, '1 = 1')
            ->leftJoin(
                Translation::class,
                'translation',
                Join::WITH,
                'translation.translationKey = e AND translation.language = language'
            )
            ->select('e.id AS translationKeyId, translation.id, translation.value')
            ->where('e.domain = :domain AND e.key = :key AND language.code = :langCode')
            ->setParameter('domain', $domain)
            ->setParameter('key', $key)
            ->setParameter('langCode', $languageCode);

        $data = $this->queryAclHelper->protectQuery(clone $qb, $config, $requestType)->getArrayResult();
        if (!$data) {
            $notAclProtectedData = $qb->getQuery()->getArrayResult();
            if ($notAclProtectedData) {
                throw new AccessDeniedException('No access to the entity.');
            }

            return null;
        }

        return new TranslationCreate(
            $domain,
            $key,
            $data[0]['translationKeyId'],
            $languageCode,
            $data[0]['id'],
            $data[0]['value']
        );
    }
}
