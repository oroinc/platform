<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class LoadFromMetadata implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var RelationConfigContext $context */

        $config = $context->getResult();
        if (null !== $config) {
            // a config already exists
            return;
        }

        $entityClass = $context->getClassName();
        if (!$entityClass || !$this->doctrineHelper->isManageableEntity($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $targetIdFields = $this->doctrineHelper->getEntityIdentifierFieldNames($entityClass);
        $config         = [
            ConfigUtil::EXCLUSION_POLICY => ConfigUtil::EXCLUSION_POLICY_ALL,
            ConfigUtil::FIELDS           => count($targetIdFields) === 1
                ? reset($targetIdFields)
                : array_fill_keys($targetIdFields, null)
        ];
        $context->setResult($config);
    }
}
