<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Creates new AsyncOperation entity and stores its ID to the context.
 */
class CreateAsyncOperation implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        if ($context->getOperationId()) {
            // an async operation was already created
            return;
        }

        $fileName = $context->getTargetFileName();
        if (!$fileName) {
            throw new \RuntimeException('The target file name was not set to the context.');
        }

        if (!$this->isCreationOfAsyncOperationGranted()) {
            throw new AccessDeniedException('No access to create the asynchronous operation.');
        }

        $operation = new AsyncOperation();
        $operation->setActionName($context->getAction());
        $operation->setEntityClass($context->getClassName());
        $operation->setDataFileName($fileName);
        $operation->setStatus(AsyncOperation::STATUS_NEW);

        $em = $this->doctrineHelper->getEntityManager($operation, false);
        $em->persist($operation);
        $em->flush();

        $context->setOperationId($operation->getId());
    }

    private function isCreationOfAsyncOperationGranted(): bool
    {
        $oid = ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, AsyncOperation::class);

        return
            $this->authorizationChecker->isGranted('CREATE', $oid)
            && $this->authorizationChecker->isGranted('VIEW', $oid);
    }
}
