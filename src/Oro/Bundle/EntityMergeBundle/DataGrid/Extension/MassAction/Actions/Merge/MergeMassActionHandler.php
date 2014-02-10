<?php

namespace Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction\Actions\Merge;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class MergeMassActionHandler implements MassActionHandlerInterface
{
    /**
     * @var Registry
     */
    private $doctrineRegistry;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrineRegistry(Registry $doctrine)
    {
        $this->doctrineRegistry = $doctrine;
    }

    /**
     * Handle mass action
     *
     * @param MassActionHandlerArgs $args

     * @throws \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @return EntityData
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $options = $args->getMassAction()->getOptions()->toArray();

        if (empty($options['entity_name'])) {
            throw new InvalidArgumentException('Entity name is missing');
        }

        $entityIdentifier = $this->getEntityIdentifier($options['entity_name']);

        $entityArray = $args->getResults()->getSource()->getQuery()->getArrayResult();
        $entityIds = array();
        foreach ($entityArray as $entity) {
            $entityIds[] = $entity[$entityIdentifier];
        }

        if (empty($options['max_element_count']) || (int)$options['max_element_count']<2) {
            throw new InvalidArgumentException('Max element count invalid');
        }

        $maxCountOfElements = $options['max_element_count'];

        $countOfSelectedItems = count($entityIds);
        $this->validateItemsCount($countOfSelectedItems, $maxCountOfElements);

        $repository = $this->getRepository($this->getEntityManager(), $options['entity_name']);

        if ($repository->getClassName() != $options['entity_name']) {
            throw new InvalidArgumentException('Incorrect repository returned');
        }

        $queryBuilder = $repository->createQueryBuilder('entity');

        $identifierExpression = sprintf('entity.%s', $entityIdentifier);

        $queryBuilder->add('where', $queryBuilder->expr()->in($identifierExpression, $entityIds));

        $entities = $queryBuilder->getQuery()->execute();

        return array('entities' => $entities, 'options' => $options);
    }

    /**
     * @param EntityManager $manager
     * @param string $entityName
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRepository(EntityManager $manager, $entityName)
    {
        return $manager->getRepository($entityName);
    }

    /**
     * @param string $className
     * @return string
     */
    protected function getEntityIdentifier($className)
    {
        return $this->getEntityManager()->getClassMetadata($className)->getSingleIdentifierFieldName();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrineRegistry->getManager();
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
}
