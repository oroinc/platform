<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\SearchBundle\Api\Repository\SearchEntityRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Loads an entity available for the search API resource.
 */
class LoadSearchEntity implements ProcessorInterface
{
    public function __construct(
        private readonly SearchEntityRepository $searchEntityRepository
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $searchEntity = $this->searchEntityRepository->findSearchEntity(
            $context->getId(),
            $context->getVersion(),
            $context->getRequestType()
        );
        if (!$searchEntity) {
            throw new NotFoundHttpException();
        }

        $context->setResult($searchEntity);
    }
}
