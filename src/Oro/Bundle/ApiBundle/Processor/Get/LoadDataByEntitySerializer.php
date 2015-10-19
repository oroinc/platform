<?php

namespace Oro\Bundle\ApiBundle\Processor\Get;

use Doctrine\ORM\QueryBuilder;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Bundle\ApiBundle\Serializer\EntitySerializerConfigBag;

class LoadDataByEntitySerializer implements ProcessorInterface
{
    /** @var EntitySerializerConfigBag */
    protected $configBag;

    /** @var EntitySerializer */
    protected $entitySerializer;

    /**
     * @param EntitySerializerConfigBag $configBag
     * @param EntitySerializer          $entitySerializer
     */
    public function __construct(
        EntitySerializerConfigBag $configBag,
        EntitySerializer $entitySerializer
    ) {
        $this->configBag        = $configBag;
        $this->entitySerializer = $entitySerializer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            // unsupported query
            return;
        }

        $entityClass = $context->getClassName();
        $version     = $context->getVersion();
        if (!$entityClass || !$this->configBag->hasConfig($entityClass, $version)) {
            // an entity does not have a configuration for the EntitySerializer
            return;
        }

        $result = $this->entitySerializer->serialize(
            $query,
            $this->configBag->getConfig($entityClass, $version)
        );
        if (empty($result)) {
            $result = null;
        } elseif (count($result) === 1) {
            $result = reset($result);
        } else {
            throw new \RuntimeException('The result must have one or zero items.');
        }

        $context->setResult($result);

        // data returned by the EntitySerializer are already normalized
        $context->skipGroup('normalize_data');
    }
}
