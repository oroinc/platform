<?php

namespace Oro\Bundle\TagBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Computes a value of "tags" association for taggable entities.
 */
class ComputeTags implements ProcessorInterface
{
    private const FIELD_NAME = 'tags';

    private TaggableHelper $taggableHelper;
    private DoctrineHelper $doctrineHelper;
    private EntitySerializer $entitySerializer;
    private AclHelper $aclHelper;

    public function __construct(
        TaggableHelper $taggableHelper,
        DoctrineHelper $doctrineHelper,
        EntitySerializer $entitySerializer,
        AclHelper $aclHelper
    ) {
        $this->taggableHelper = $taggableHelper;
        $this->doctrineHelper = $doctrineHelper;
        $this->entitySerializer = $entitySerializer;
        $this->aclHelper = $aclHelper;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $entityClass = $context->getClassName();
        if (!$this->taggableHelper->isTaggable($entityClass)) {
            return;
        }

        $data = $context->getData();
        if (!$context->isFieldRequestedForCollection(self::FIELD_NAME, $data)) {
            return;
        }

        $config = $context->getConfig();
        $idFieldName = $this->getIdentifierFieldName($entityClass, $config);
        if (!$idFieldName) {
            return;
        }

        $ids = [];
        foreach ($data as $item) {
            $ids[] = $item[$idFieldName];
        }

        $tags = $this->loadTagsAssociationData(
            $entityClass,
            $ids,
            $config->getField(self::FIELD_NAME)->getTargetEntity(),
            $context->getNormalizationContext()
        );
        foreach ($data as $key => $item) {
            $data[$key][self::FIELD_NAME] = $tags[$item[$idFieldName]] ?? [];
        }

        $context->setData($data);
    }

    private function getIdentifierFieldName(string $entityClass, EntityDefinitionConfig $config): ?string
    {
        $idFieldName = $this->doctrineHelper->getSingleEntityIdentifierFieldNameForClass($entityClass, false);
        if (!$idFieldName) {
            return null;
        }

        return $config->findFieldNameByPropertyPath($idFieldName);
    }

    private function loadTagsAssociationData(
        string $entityClass,
        array $ids,
        EntityDefinitionConfig $config,
        array $normalizationContext
    ): array {
        $qb = $this->doctrineHelper->createQueryBuilder(Tagging::class, 'tagging')
            ->select('tagging, tag')
            ->innerJoin('tagging.tag', 'tag')
            ->andWhere('tagging.entityName = :class AND tagging.recordId IN (:ids)')
            ->setParameter('class', $entityClass)
            ->setParameter('ids', $ids);
        $rows = $this->aclHelper->apply($qb)->getResult();
        $tags = [];
        $map = [];
        /** @var Tagging $row */
        foreach ($rows as $row) {
            $tag = $row->getTag();
            $tags[$tag->getId()] = $tag;
            $map[$tag->getId()][] = $row->getRecordId();
        }
        $tags = array_values($tags);

        $data = $this->entitySerializer->serializeEntities($tags, Tag::class, $config, $normalizationContext);

        $result = [];
        $tagIdFieldName = $this->getTagIdentifierFieldName($config);
        foreach ($data as $item) {
            $tagId = $item[$tagIdFieldName];
            foreach ($map[$tagId] as $entityId) {
                $result[$entityId][] = $item;
            }
        }

        return $result;
    }

    private function getTagIdentifierFieldName(EntityDefinitionConfig $config): string
    {
        $idFieldNames = $config->getIdentifierFieldNames();

        return reset($idFieldNames);
    }
}
