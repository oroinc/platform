<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes IDs of email thread context items and adds them to the "meta" section
 * of the "activityTargets" relationship.
 */
class ComputeEmailThreadContextItemForActivityTargets implements ProcessorInterface
{
    private const ACTIVITY_TARGETS_FIELD_NAME = 'activityTargets';

    private DoctrineHelper $doctrineHelper;
    private ValueNormalizer $valueNormalizer;

    public function __construct(DoctrineHelper $doctrineHelper, ValueNormalizer $valueNormalizer)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        if (!$context->isFieldRequested(self::ACTIVITY_TARGETS_FIELD_NAME)) {
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            return;
        }
        $activityTargetConfig = $config->getField(self::ACTIVITY_TARGETS_FIELD_NAME)?->getTargetEntity();
        if (null === $activityTargetConfig) {
            return;
        }

        $data = $context->getData();
        if (!$data[self::ACTIVITY_TARGETS_FIELD_NAME]) {
            return;
        }

        $requestType = $context->getRequestType();
        $emailId = $this->getThreadEmailId($data[$config->findFieldNameByPropertyPath('id')]);
        $activityTargetIdFieldName = $this->getIdentifierFieldName($activityTargetConfig);
        foreach ($data[self::ACTIVITY_TARGETS_FIELD_NAME] as $t => $activityTargetItem) {
            $data[self::ACTIVITY_TARGETS_FIELD_NAME][$t]['emailThreadContextItemId'] =
                $this->buildEmailThreadContextItemId(
                    $activityTargetItem[ConfigUtil::CLASS_NAME],
                    $activityTargetItem[$activityTargetIdFieldName],
                    $emailId,
                    $requestType
                );
        }

        $context->setData($data);
    }

    private function buildEmailThreadContextItemId(
        string $entityClass,
        mixed $entityId,
        int $emailId,
        RequestType $requestType
    ): string {
        return sprintf(
            '%s-%s-%d',
            ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, $entityClass, $requestType),
            $entityId,
            $emailId
        );
    }

    private function getThreadEmailId(int $emailId): int
    {
        $rows = $this->doctrineHelper->createQueryBuilder(Email::class, 'e')
            ->select('e.id, e.subject')
            ->innerJoin(Email::class, 'p', Join::WITH, 'e.id = p.id OR e.thread = p.thread')
            ->where('p.id = :emailId')
            ->setParameter('emailId', $emailId)
            ->getQuery()
            ->getArrayResult();
        if (!$rows) {
            return 0;
        }

        $emailIds = array_column($rows, 'id');
        sort($emailIds);

        return reset($emailIds);
    }

    private function getIdentifierFieldName(EntityDefinitionConfig $config): string
    {
        $entityIdFieldNames = $config->getIdentifierFieldNames();

        return reset($entityIdFieldNames);
    }
}
