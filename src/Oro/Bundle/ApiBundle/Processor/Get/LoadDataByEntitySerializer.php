<?php

namespace Oro\Bundle\ApiBundle\Processor\Get;

use Doctrine\ORM\QueryBuilder;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;

class LoadDataByEntitySerializer implements ProcessorInterface
{
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var EntitySerializer */
    protected $entitySerializer;

    /**
     * @param ConfigProvider   $configProvider
     * @param EntitySerializer $entitySerializer
     */
    public function __construct(
        ConfigProvider $configProvider,
        EntitySerializer $entitySerializer
    ) {
        $this->configProvider   = $configProvider;
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
        if (!$entityClass) {
            // an entity type is not specified
            return;
        }

        $config = $this->configProvider->getConfig(
            $entityClass,
            $context->getVersion(),
            $context->getRequestType(),
            $context->getAction()
        );
        if (empty($config['definition'])) {
            // an entity does not have a configuration for the EntitySerializer
            return;
        }

        $result = $this->entitySerializer->serialize($query, $config['definition']);
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
