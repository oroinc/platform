<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityIdMetadataInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentEntityAccess;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\ApiBundle\Util\QueryAclHelper;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Checks whether an VIEW access to the parent email entity is granted.
 */
class ValidateParentEmailAccess implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private EntityIdHelper $entityIdHelper;
    private QueryAclHelper $queryAclHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityIdHelper $entityIdHelper,
        QueryAclHelper $queryAclHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityIdHelper = $entityIdHelper;
        $this->queryAclHelper = $queryAclHelper;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        $this->checkParentEntityAccess(
            $context->getParentId(),
            $context->getParentConfig(),
            $context->getParentMetadata(),
            $context->getRequestType()
        );

        $context->setProcessed(ValidateParentEntityAccess::OPERATION_NAME);
    }

    private function checkParentEntityAccess(
        mixed $parentEntityId,
        EntityDefinitionConfig $parentConfig,
        EntityIdMetadataInterface $parentMetadata,
        RequestType $requestType
    ): void {
        // try to get an entity by ACL protected query
        $data = $this->queryAclHelper
            ->protectQuery(
                $this->getEmailWithEmailUserRestrictionQueryBuilder($parentEntityId, $parentMetadata),
                $parentConfig,
                $requestType
            )
            ->getOneOrNullResult(Query::HYDRATE_ARRAY);
        if (!$data) {
            // use a query without ACL protection to check if an entity exists in DB
            $data = $this->getEmailQueryBuilder($parentEntityId, $parentMetadata)
                ->getQuery()
                ->getOneOrNullResult(Query::HYDRATE_ARRAY);
            if ($data) {
                throw new AccessDeniedException('No access to the parent entity.');
            }
            throw new NotFoundHttpException('The parent entity does not exist.');
        }
    }

    private function getEmailWithEmailUserRestrictionQueryBuilder(
        mixed $parentEntityId,
        EntityIdMetadataInterface $parentMetadata
    ): QueryBuilder {
        $qb = $this->getEmailQueryBuilder($parentEntityId, $parentMetadata);
        $qb->andWhere($qb->expr()->exists(
            $this->doctrineHelper->createQueryBuilder(EmailUser::class, 'email_users')
                ->where('email_users.email = e')
                ->getDQL()
        ));

        return $qb;
    }

    private function getEmailQueryBuilder(
        mixed $parentEntityId,
        EntityIdMetadataInterface $parentMetadata
    ): QueryBuilder {
        $qb = $this->doctrineHelper->createQueryBuilder(Email::class, 'e');
        $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass(Email::class);
        if (\count($idFieldNames) !== 0) {
            $qb->select('e.' . reset($idFieldNames));
        }
        $this->entityIdHelper->applyEntityIdentifierRestriction(
            $qb,
            $parentEntityId,
            $parentMetadata
        );

        return $qb;
    }
}
