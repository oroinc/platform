<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Computes a value of "path" field for an entity that is a node of a tree.
 */
class ComputeTreeNodePathField implements ProcessorInterface
{
    protected EntitySerializer $entitySerializer;
    protected DoctrineHelper $doctrineHelper;
    protected string $pathField;
    protected string $materializedPathField = 'materializedPath';
    protected string $materializedPathDelimiter = '_';
    protected ?string $sourceEntityClass = null;

    public function __construct(
        EntitySerializer $entitySerializer,
        DoctrineHelper $doctrineHelper,
        string $pathField = 'path'
    ) {
        $this->entitySerializer = $entitySerializer;
        $this->doctrineHelper = $doctrineHelper;
        $this->pathField = $pathField;
    }

    public function setMaterializedPathField(string $materializedPathField): void
    {
        $this->materializedPathField = $materializedPathField;
    }

    public function setMaterializedPathDelimiter(string $materializedPathDelimiter): void
    {
        $this->materializedPathDelimiter = $materializedPathDelimiter;
    }

    public function setSourceEntityClass(string $sourceEntityClass): void
    {
        $this->sourceEntityClass = $sourceEntityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if (!$context->isFieldRequestedForCollection($this->pathField, $data)) {
            return;
        }

        $config = $context->getConfig();
        $nodeEntityClass = $this->getNodeEntityClass($context, $config);
        $nodeEntityIdFieldName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($nodeEntityClass);
        $idFieldName = $context->getResultFieldName($nodeEntityIdFieldName);

        $parentNodes = $this->getParentNodes($data, $idFieldName);

        $nodes = $this->loadNodesData(
            $nodeEntityClass,
            $nodeEntityIdFieldName,
            $this->getNodeIds($parentNodes),
            $config->getField($this->pathField)->getTargetEntity(),
            $context->getNormalizationContext()
        );

        foreach ($data as $key => $item) {
            $pathNodes = [];
            $id = $item[$idFieldName];
            foreach ($parentNodes[$id] as $nodeId) {
                if (!empty($nodes[$nodeId])) {
                    $pathNodes[] = $nodes[$nodeId];
                }
            }
            $data[$key][$this->pathField] = $pathNodes;
        }

        $context->setData($data);
    }

    protected function getNodeEntityClass(CustomizeLoadedDataContext $context, EntityDefinitionConfig $config): string
    {
        return $this->doctrineHelper->getManageableEntityClass(
            $this->sourceEntityClass ?? $context->getClassName(),
            $config
        );
    }

    /**
     * @return array [node id => [parent node id, ...], ...]
     */
    protected function getParentNodes(array $data, string $idFieldName): array
    {
        $parentNodes = [];
        foreach ($data as $item) {
            $parentIds = [];
            $materializedPath = explode($this->materializedPathDelimiter, $item[$this->materializedPathField]);
            // skip the last element because it is the same as the current node id
            $lastIndex = \count($materializedPath) - 2;
            for ($i = 0; $i <= $lastIndex; $i++) {
                $parentIds[] = (int)$materializedPath[$i];
            }
            $parentNodes[$item[$idFieldName]] = $parentIds;
        }

        return $parentNodes;
    }

    /**
     * @param array $parentNodes [node id => [parent node id, ...], ...]
     *
     * @return array
     */
    protected function getNodeIds(array $parentNodes): array
    {
        return array_values(array_unique(array_merge(...array_values($parentNodes)), SORT_NUMERIC));
    }

    /**
     * @return array [node id => node data, ...]
     */
    protected function loadNodesData(
        string $nodeEntityClass,
        string $nodeEntityIdFieldName,
        array $nodeIds,
        EntityDefinitionConfig $config,
        array $normalizationContext
    ): array {
        $qb = $this->getQueryForLoadNodes($nodeEntityClass, $nodeIds);

        $nodes = $this->entitySerializer->serialize($qb, $config, $normalizationContext);

        $result = [];
        foreach ($nodes as $node) {
            $result[$node[$nodeEntityIdFieldName]] = $node;
        }

        return $result;
    }

    protected function getQueryForLoadNodes(string $nodeEntityClass, array $nodeIds): QueryBuilder
    {
        return $this->doctrineHelper
            ->createQueryBuilder($nodeEntityClass, 'n')
            ->where('n IN (:ids)')
            ->setParameter('ids', $nodeIds);
    }
}
