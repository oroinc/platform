<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EmailBundle\Api\Model\EmailThreadContextItemDelete;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Adds an instance of EmailThreadContextItem that corresponds a submitted entity identifier to the context.
 */
class LoadEmailThreadContextItem implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private ValueNormalizer $valueNormalizer;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ValueNormalizer $valueNormalizer,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->valueNormalizer = $valueNormalizer;
        $this->authorizationChecker = $authorizationChecker;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        if ($context->hasResult()) {
            // the entity is already loaded
            return;
        }

        $id = $context->getId();
        $emailIdPos = strrpos($id, '-');
        if (false === $emailIdPos) {
            return;
        }
        $entityIdPos = strrpos($id, '-', -(\strlen($id) - $emailIdPos + 1));
        if (false === $entityIdPos) {
            return;
        }

        $requestType = $context->getRequestType();
        $emailId = $this->concertToInt(substr($id, $emailIdPos + 1), $requestType);
        if (null === $emailId || 0 === $emailId) {
            return;
        }
        $entityId = $this->concertToInt(substr($id, $entityIdPos + 1, $emailIdPos - $entityIdPos - 1), $requestType);
        if (null === $entityId) {
            return;
        }
        $entityClass = $this->concertToEntityClass(substr($id, 0, $entityIdPos), $requestType);
        if (null === $entityClass) {
            return;
        }

        $emailEntity = $this->doctrineHelper->getEntityReference(Email::class, $emailId);
        if (!$this->authorizationChecker->isGranted(BasicPermission::VIEW, $emailEntity)) {
            throw new AccessDeniedException(sprintf(
                'No access by "%s" permission to the email entity.',
                BasicPermission::VIEW
            ));
        }

        $context->setResult(new EmailThreadContextItemDelete(
            $id,
            $this->doctrineHelper->getEntityReference($entityClass, $entityId),
            $emailEntity
        ));
    }

    private function concertToEntityClass(string $value, RequestType $requestType): ?string
    {
        return ValueNormalizerUtil::tryConvertToEntityClass($this->valueNormalizer, $value, $requestType);
    }

    private function concertToInt(string $value, RequestType $requestType): ?int
    {
        try {
            return $this->valueNormalizer->normalizeValue($value, DataType::INTEGER, $requestType);
        } catch (\UnexpectedValueException) {
        }

        return null;
    }
}
