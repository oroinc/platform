<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class GetConfigFromMetadata implements ProcessorInterface
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
        /** @var GetConfigContext $context */

        if ($context->hasResult()) {
            // config is already set
            return;
        }

        $entityClass = $context->getClassName();
        if (!$entityClass || !$this->doctrineHelper->isManageableEntity($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $config = [
            'exclusion_policy' => 'all'
        ];

        $metadata = $this->doctrineHelper->getEntityMetadata($entityClass);

        $fields = array_fill_keys($metadata->getFieldNames(), null);

        $config['fields'] = $fields;

        $context->setResult($config);
    }
}
