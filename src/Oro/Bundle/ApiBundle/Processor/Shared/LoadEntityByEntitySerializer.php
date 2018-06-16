<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Loads entity using the EntitySerializer component.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
 */
class LoadEntityByEntitySerializer implements ProcessorInterface
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
        /** @var Context $context */

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

        $result = $this->entitySerializer->serialize(
            $query,
            $config,
            [
                Context::ACTION       => $context->getAction(),
                Context::VERSION      => $context->getVersion(),
                Context::REQUEST_TYPE => $context->getRequestType()
            ]
        );
        if (empty($result)) {
            $result = null;
        } elseif (count($result) === 1) {
            $result = reset($result);
        } else {
            throw new RuntimeException('The result must have one or zero items.');
        }

        $context->setResult($result);

        // data returned by the EntitySerializer are already normalized
        $context->skipGroup('normalize_data');
    }
}
