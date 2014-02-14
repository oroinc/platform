<?php

namespace Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;

use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class MergeMassActionHandler implements MassActionHandlerInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

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
    public function handle(MassActionHandlerArgs $args)
    {
        $massAction = $args->getMassAction();
        $options = $massAction->getOptions()->toArray();

        if (empty($options['entity_name'])) {
            throw new InvalidArgumentException('Entity name is missing.');
        }

        $entityIdentifier = $this->doctrineHelper->getSingleIdentifierFieldName($options['entity_name']);
        $entityIds = $this->getIdsFromResult($args->getResults(), $entityIdentifier);

        $entities = $this->doctrineHelper->getEntitiesByIds(
            $options['entity_name'],
            $entityIds
        );

        return new MassActionResponse(
            true,
            null,
            array(
                'entities' => $entities,
                'entity_name' => $options['entity_name'],
                'options' => $options
            )
        );
    }

    /**
     * @param IterableResultInterface $iterated
     * @param string $entityIdentifier
     * @return array
     */
    protected function getIdsFromResult(IterableResultInterface $iterated, $entityIdentifier)
    {
        $entityIds = array();
        /** @var ResultRecord $entity */
        foreach ($iterated as $entity) {
            $entityIds[] = $entity->getValue($entityIdentifier);
        }
        return $entityIds;
    }
}
