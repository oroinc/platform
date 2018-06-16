<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Loads entities using the EntitySerializer component.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
 */
class LoadEntitiesByEntitySerializer implements ProcessorInterface
{
    /** @var EntitySerializer */
    protected $entitySerializer;

    /**
     * @param EntitySerializer $entitySerializer
     */
    public function __construct(EntitySerializer $entitySerializer)
    {
        $this->entitySerializer = $entitySerializer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            // unsupported query
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // only configured API resources are supported
            return;
        }

        $context->setResult(
            $this->entitySerializer->serialize(
                $query,
                $config,
                [
                    Context::ACTION       => $context->getAction(),
                    Context::VERSION      => $context->getVersion(),
                    Context::REQUEST_TYPE => $context->getRequestType()
                ]
            )
        );

        // data returned by the EntitySerializer are already normalized
        $context->skipGroup('normalize_data');
    }
}
