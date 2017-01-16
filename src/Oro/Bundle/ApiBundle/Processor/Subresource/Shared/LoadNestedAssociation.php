<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Doctrine\ORM\QueryBuilder;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Loads nested association data using the EntitySerializer component.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
 */
class LoadNestedAssociation implements ProcessorInterface
{
    /** @var EntitySerializer */
    protected $entitySerializer;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param EntitySerializer $entitySerializer
     * @param DoctrineHelper   $doctrineHelper
     */
    public function __construct(EntitySerializer $entitySerializer, DoctrineHelper $doctrineHelper)
    {
        $this->entitySerializer = $entitySerializer;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $associationName = $context->getAssociationName();
        $parentConfig = $context->getParentConfig();
        if (!$this->isNestedAssociation($associationName, $parentConfig)) {
            // only nested association is supported
            return;
        }

        $context->setResult(
            $this->loadData($context, $associationName)
        );

        // data returned by the EntitySerializer are already normalized
        $context->skipGroup('normalize_data');
    }

    /**
     * @param string                 $associationName
     * @param EntityDefinitionConfig $parentConfig
     *
     * @return bool
     */
    protected function isNestedAssociation($associationName, EntityDefinitionConfig $parentConfig)
    {
        $associationConfig = $parentConfig->getField($associationName);

        return null !== $associationConfig
            ? DataType::isNestedAssociation($associationConfig->getDataType())
            : false;
    }

    /**
     * @param SubresourceContext $context
     * @param string             $associationName
     *
     * @return array|null
     */
    protected function loadData(SubresourceContext $context, $associationName)
    {
        $data = $this->entitySerializer->serialize(
            $this->getQueryBuilder($context->getParentClassName(), $context->getParentId()),
            $context->getParentConfig()
        );
        $data = reset($data);
        if (empty($data) || !array_key_exists($associationName, $data)) {
            return null;
        }

        $result = $data[$associationName];
        if (null !== $result && empty($result)) {
            $result = null;
        }

        return $result;
    }

    /**
     * @param string $parentEntityClass
     * @param mixed  $parentEntityId
     *
     * @return QueryBuilder
     */
    protected function getQueryBuilder($parentEntityClass, $parentEntityId)
    {
        $query = $this->doctrineHelper->getEntityRepositoryForClass($parentEntityClass)->createQueryBuilder('e');
        $this->doctrineHelper->applyEntityIdentifierRestriction(
            $query,
            $parentEntityClass,
            $parentEntityId
        );

        return $query;
    }
}
