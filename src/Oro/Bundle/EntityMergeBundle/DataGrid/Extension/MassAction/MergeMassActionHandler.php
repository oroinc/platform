<?php

namespace Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\EntityMergeBundle\Data\EntityProvider;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class MergeMassActionHandler implements MassActionHandlerInterface
{

    /**
     * @var EntityProvider $entityProvider ;
     */
    private $entityProvider;


    public function setEntityProvider(EntityProvider $entityProvider)
    {
        $this->entityProvider = $entityProvider;
    }

    /**
     * Handle mass action
     *
     * @param MassActionHandlerArgs $args
     * @throws \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @return array array('entities' => $entities, 'options' => $options)
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $options = $this->getOptionsArray($args);

        if (empty($options['entity_name'])) {
            throw new InvalidArgumentException('Entity name is missing');
        }

        $entityIdentifier = $this->entityProvider->getEntityIdentifier($options['entity_name']);

        $entityIds = $this->getIdsFromResult($args->getResults(), $entityIdentifier);

        if (empty($options['max_element_count']) || (int)$options['max_element_count'] < 2) {
            throw new InvalidArgumentException('Max element count invalid');
        }

        $maxCountOfElements = $options['max_element_count'];

        $countOfSelectedItems = count($entityIds);
        $this->validateItemsCount($countOfSelectedItems, $maxCountOfElements);

        $entities = $this->entityProvider->getEntitiesByIds(
            $options['entity_name'],
            $entityIds
        );

        return array('entities' => $entities, 'options' => $options);
    }


    /**
     * @param $countOfSelectedItems
     * @param $maxCountOfElements
     * @throws InvalidArgumentException
     */
    public function validateItemsCount($countOfSelectedItems, $maxCountOfElements)
    {
        if ($countOfSelectedItems < 2) {
            throw new InvalidArgumentException('Count of selected items less then 2');
        } elseif ($countOfSelectedItems > $maxCountOfElements) {
            throw new InvalidArgumentException('Too many items selected');
        }
    }

    /**
     * @param MassActionHandlerArgs $args
     * @return array
     */
    public function getSelectedEntityArrayFromArgs(MassActionHandlerArgs $args)
    {
        return $args->getResults()->getSource()->getQuery()->getArrayResult();
    }

    /**
     * @param MassActionHandlerArgs $args
     * @return array
     */
    public function getOptionsArray(MassActionHandlerArgs $args)
    {
        return $args->getMassAction()->getOptions()->toArray();
    }

    /**
     * @param \Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface $iterated
     * @param $entityIdentifier
     * @internal param $entityArray
     * @return array
     */
    public function getIdsFromResult(IterableResultInterface $iterated, $entityIdentifier)
    {
        $entityIds = array();
        foreach ($iterated as $entity) {
            /**
             * @var ResultRecord $entity
             */
            $entityIds[] = $entity->getValue($entityIdentifier);
        }
        return $entityIds;
    }
}
