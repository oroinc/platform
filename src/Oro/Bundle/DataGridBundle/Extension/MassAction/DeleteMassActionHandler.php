<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datasource\Orm\DeletionIterableResult;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete\MassDeleteLimiter;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete\MassDeleteLimitResult;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * DataGrid mass action handler for mass delete action.
 */
class DeleteMassActionHandler implements MassActionHandlerInterface
{
    public const FLUSH_BATCH_SIZE = 100;

    /** @var int[]  */
    protected array $postponedIds = [];

    protected string $responseMessage = 'oro.grid.mass_action.delete.success_message';

    public function __construct(
        protected ManagerRegistry $registry,
        protected TranslatorInterface $translator,
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected MassDeleteLimiter $limiter,
        protected RequestStack $requestStack
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function handle(MassActionHandlerArgs $args): MassActionResponseInterface
    {
        $limitResult = $this->limiter->getLimitResult($args);
        $method      = $this->requestStack->getMainRequest()->getMethod();
        if ($method === 'POST') {
            $result = $this->getPostResponse($limitResult);
        } elseif ($method === 'DELETE') {
            $this->limiter->limitQuery($limitResult, $args);
            $result = $this->doDelete($args);
        } else {
            $result = $this->getNotSupportedResponse($method);
        }

        return $result;
    }

    /**
     * Finish processed batch
     */
    protected function finishBatch(EntityManagerInterface $manager): void
    {
        $manager->flush();
        $manager->clear();
    }

    protected function getDeleteResponse(
        MassActionHandlerArgs $args,
        int $entitiesCount = 0
    ): MassActionResponseInterface {
        $massAction      = $args->getMassAction();
        $responseMessage = $massAction->getOptions()->offsetGetByPath('[messages][success]', $this->responseMessage);

        $successful = $entitiesCount > 0;
        $options    = ['count' => $entitiesCount];

        return new MassActionResponse(
            $successful,
            $this->translator->trans(
                $responseMessage,
                ['%count%' => $entitiesCount]
            ),
            $options
        );
    }

    protected function getPostResponse(MassDeleteLimitResult $limitResult): MassActionResponseInterface
    {
        return new MassActionResponse(
            true,
            'OK',
            [
                'selected'  => $limitResult->getSelected(),
                'deletable' => $limitResult->getDeletable(),
                'max_limit' => $limitResult->getMaxLimit()
            ]
        );
    }

    protected function getNotSupportedResponse(string $method): MassActionResponseInterface
    {
        return new MassActionResponse(
            false,
            sprintf('Method "%s" is not supported', $method)
        );
    }

    protected function getEntityName(MassActionHandlerArgs $args): string
    {
        $massAction = $args->getMassAction();
        $entityName = $massAction->getOptions()->offsetGet('entity_name');
        if (!$entityName) {
            throw new LogicException(sprintf('Mass action "%s" must define entity name', $massAction->getName()));
        }

        return $entityName;
    }

    protected function getEntityIdentifierField(MassActionHandlerArgs $args): string
    {
        $massAction = $args->getMassAction();
        $identifier = $massAction->getOptions()->offsetGet('data_identifier');
        if (!$identifier) {
            throw new LogicException(sprintf('Mass action "%s" must define identifier name', $massAction->getName()));
        }

        // if we ask identifier that's means that we have plain data in array
        // so we will just use column name without entity alias
        if (str_contains($identifier, '.')) {
            $parts = explode('.', $identifier);
            $identifier = end($parts);
        }

        return $identifier;
    }

    protected function doDelete(MassActionHandlerArgs $args): MassActionResponseInterface
    {
        $iteration = 0;
        $entityName = $this->getEntityName($args);
        $queryBuilder = $args->getResults()->getSource();
        // if huge amount data must be deleted
        set_time_limit(0);
        $entityIdentifiedField = $this->getEntityIdentifierField($args);

        $this->doIterate($queryBuilder, $entityName, $entityIdentifiedField, $iteration);
        if ($this->postponedIds) {
            $qb = $this->getPostponedEntitiesQB($entityName, $entityIdentifiedField);
            $this->doIterate($qb, $entityName, $entityIdentifiedField, $iteration);
            $this->postponedIds = [];
        }

        return $this->getDeleteResponse($args, $iteration);
    }

    protected function doIterate(
        Query|QueryBuilder $queryBuilder,
        string $entityName,
        string $entityIdentifiedField,
        int &$iteration
    ): void {
        $results = new DeletionIterableResult($queryBuilder);
        $results->setBufferSize(self::FLUSH_BATCH_SIZE);

        /** @var EntityManagerInterface $manager */
        $manager = $this->registry->getManagerForClass($entityName);
        /** @var ResultRecordInterface $result */
        foreach ($results as $result) {
            $entity = $result->getRootEntity();
            $identifierValue = $result->getValue($entityIdentifiedField);
            if (!$entity) {
                // no entity in result record, it should be extracted from DB
                $entity = $manager->getReference($entityName, $identifierValue);
            }

            if ($entity) {
                if ($this->isPostponed($entity)) {
                    $this->postponedIds[] = $entity->getId();
                    continue;
                }

                if (!$this->isDeleteAllowed($entity)) {
                    continue;
                }

                $this->processDelete($entity, $manager);
                $iteration++;

                if ($iteration % self::FLUSH_BATCH_SIZE === 0) {
                    $this->finishBatch($manager);
                }
            }
        }

        if ($iteration % self::FLUSH_BATCH_SIZE > 0) {
            $this->finishBatch($manager);
        }
    }

    protected function isPostponed(object $entity): bool
    {
        return false;
    }

    protected function isDeleteAllowed(object $entity): bool
    {
        return $this->authorizationChecker->isGranted('DELETE', $entity);
    }

    protected function processDelete(object $entity, EntityManagerInterface $manager): void
    {
        $manager->remove($entity);
    }

    protected function getPostponedEntitiesQB(string $entityName, string $entityIdentifiedField): QueryBuilder
    {
        $qb = $this->registry
            ->getRepository($entityName)
            ->createQueryBuilder('e');

        $qb->where($qb->expr()->in(QueryBuilderUtil::getField('e', $entityIdentifiedField), ':ids'))
            ->setParameter('ids', $this->postponedIds);

        return $qb;
    }
}
