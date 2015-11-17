<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Doctrine\ORM\QueryBuilder;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

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
        /** @var GetListContext $context */

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
        if (null === $config) {
            // a configuration was not found
            return;
        }

        $definition = ConfigUtil::getDefinition($config);
        if (empty($definition)) {
            // an entity does not have a configuration for the EntitySerializer
            return;
        }

        $context->setResult(
            $this->entitySerializer->serialize($query, $definition)
        );

        // data returned by the EntitySerializer are already normalized
        $context->skipGroup('normalize_data');
    }
}
