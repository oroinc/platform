<?php

namespace Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;

use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\MinEntitiesCount;

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
        $this->validateMassAction($massAction);
        $options = $massAction->getOptions()->toArray();

        $entityIdentifier = $this->doctrineHelper->getEntityIdentifier($options['entity_name']);
        $entityIds = $this->getIdsFromResult($args->getResults(), $entityIdentifier);

        $maxCountOfElements = $options['max_element_count'];
        $countOfSelectedItems = count($entityIds);
        $this->validateItemsCount($countOfSelectedItems, $maxCountOfElements);

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
     * @param MassActionInterface $massAction
     * @throws InvalidArgumentException
     */
    public function validateMassAction(MassActionInterface $massAction)
    {
        $options = $massAction->getOptions()->toArray();
        if (empty($options['entity_name'])) {
            throw new InvalidArgumentException('Entity name is missing.');
        }

        if (empty($options['max_element_count'])
            || (int)$options['max_element_count'] < MinEntitiesCount::MIN_ENTITIES_COUNT
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Option "max_element_count" of "%s" mass action is invalid.',
                    $massAction->getName()
                )
            );
        }
    }

    /**
     * @param int $countOfSelectedItems
     * @param int $maxCountOfElements
     * @throws InvalidArgumentException
     */
    public function validateItemsCount($countOfSelectedItems, $maxCountOfElements)
    {
        if ($countOfSelectedItems < MinEntitiesCount::MIN_ENTITIES_COUNT) {
            throw new InvalidArgumentException(
                sprintf('Count of selected items less then %s', MinEntitiesCount::MIN_ENTITIES_COUNT)
            );
        } elseif ($countOfSelectedItems > $maxCountOfElements) {
            throw new InvalidArgumentException('Too many items selected');
        }
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
