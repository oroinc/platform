<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\AsyncOperation;

use Oro\Bundle\ApiBundle\Batch\ErrorManager;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Filter\FilterHelper;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\GetSubresourceContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Loads errors occurred when processing an asynchronous operation.
 */
class LoadErrors implements ProcessorInterface
{
    private ErrorManager $errorManager;
    private FileManager $fileManager;
    private DoctrineHelper $doctrineHelper;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        ErrorManager $errorManager,
        FileManager $fileManager,
        DoctrineHelper $doctrineHelper,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->errorManager = $errorManager;
        $this->fileManager = $fileManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var GetSubresourceContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $operationId = $context->getParentId();
        $operation = $this->doctrineHelper->getEntity(AsyncOperation::class, $operationId);
        if (null === $operation) {
            throw new NotFoundHttpException('The asynchronous operation does not exist.');
        }
        if (!$this->authorizationChecker->isGranted('VIEW', $operation)) {
            throw new AccessDeniedException('No access to the asynchronous operation.');
        }

        $filterHelper = new FilterHelper($context->getFilters(), $context->getFilterValues());
        $offset = $filterHelper->getPageNumber() > 1
            ? $filterHelper->getPageSize() * ($filterHelper->getPageNumber() - 1)
            : 0;
        $limit = $filterHelper->getPageSize();
        $errors = $this->errorManager->readErrors(
            $this->fileManager,
            $operationId,
            $offset,
            (null !== $limit && $context->getConfig()->getHasMore()) ? $limit + 1 : $limit
        );

        if (null !== $limit && \count($errors) > $limit) {
            $errors = \array_slice($errors, 0, $limit);
            $errors[ConfigUtil::INFO_RECORD_KEY] = [ConfigUtil::HAS_MORE => true];
        }

        $context->setResult($errors);

        // set callback to be used to calculate total count
        $context->setTotalCountCallback(function () use ($operationId) {
            return $this->errorManager->getTotalErrorCount($this->fileManager, $operationId);
        });
    }
}
