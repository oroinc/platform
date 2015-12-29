<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete\MassDeleteLimiter;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete\MassDeleteLimitResult;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\DeletionIterableResult;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class DeleteMassActionHandler implements MassActionHandlerInterface
{
    const FLUSH_BATCH_SIZE = 100;

    /** @var EntityManager */
    protected $entityManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var MassDeleteLimiter */
    protected $limiter;

    /** @var RequestStack */
    protected $requestStack;

    /**
     * @var string
     */
    protected $responseMessage = 'oro.grid.mass_action.delete.success_message';

    /**
     * @param EntityManager       $entityManager
     * @param TranslatorInterface $translator
     * @param SecurityFacade      $securityFacade
     * @param MassDeleteLimiter   $limiter
     * @param RequestStack        $requestStack
     */
    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        SecurityFacade $securityFacade,
        MassDeleteLimiter $limiter,
        RequestStack $requestStack
    ) {
        $this->entityManager  = $entityManager;
        $this->translator     = $translator;
        $this->securityFacade = $securityFacade;
        $this->limiter        = $limiter;
        $this->requestStack   = $requestStack;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $limitResult = $this->limiter->getLimitResult($args);
        $method      = $this->requestStack->getMasterRequest()->getMethod();
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
    protected function finishBatch()
    {
        $this->entityManager->flush();
        if ($this->entityManager->getConnection()->getTransactionNestingLevel() == 1) {
            $this->entityManager->clear();
        }
    }

    /**
     * @param MassActionHandlerArgs $args
     * @param int                   $entitiesCount
     *
     * @return MassActionResponse
     */
    protected function getDeleteResponse(MassActionHandlerArgs $args, $entitiesCount = 0)
    {
        $massAction      = $args->getMassAction();
        $responseMessage = $massAction->getOptions()->offsetGetByPath('[messages][success]', $this->responseMessage);

        $successful = $entitiesCount > 0;
        $options    = ['count' => $entitiesCount];

        return new MassActionResponse(
            $successful,
            $this->translator->transChoice(
                $responseMessage,
                $entitiesCount,
                ['%count%' => $entitiesCount]
            ),
            $options
        );
    }

    /**
     * @param MassDeleteLimitResult $limitResult
     *
     * @return MassActionResponse
     */
    protected function getPostResponse(MassDeleteLimitResult $limitResult)
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

    /**
     * @param $method
     *
     * @return MassActionResponse
     */
    protected function getNotSupportedResponse($method)
    {
        return new MassActionResponse(
            false,
            sprintf('Method "%s" is not supported', $method)
        );
    }

    /**
     * @param MassActionHandlerArgs $args
     *
     * @return string
     * @throws LogicException
     */
    protected function getEntityName(MassActionHandlerArgs $args)
    {
        $massAction = $args->getMassAction();
        $entityName = $massAction->getOptions()->offsetGet('entity_name');
        if (!$entityName) {
            throw new LogicException(sprintf('Mass action "%s" must define entity name', $massAction->getName()));
        }

        return $entityName;
    }

    /**
     * @param MassActionHandlerArgs $args
     *
     * @throws LogicException
     * @return string
     */
    protected function getEntityIdentifierField(MassActionHandlerArgs $args)
    {
        $massAction = $args->getMassAction();
        $identifier = $massAction->getOptions()->offsetGet('data_identifier');
        if (!$identifier) {
            throw new LogicException(sprintf('Mass action "%s" must define identifier name', $massAction->getName()));
        }

        // if we ask identifier that's means that we have plain data in array
        // so we will just use column name without entity alias
        if (strpos('.', $identifier) !== -1) {
            $parts      = explode('.', $identifier);
            $identifier = end($parts);
        }

        return $identifier;
    }

    /**
     * @param string $entityName
     * @param mixed  $identifierValue
     *
     * @return object
     */
    protected function getEntity($entityName, $identifierValue)
    {
        return $this->entityManager->getReference($entityName, $identifierValue);
    }

    /**
     * @param MassActionHandlerArgs $args
     *
     * @return MassActionResponse
     * @throws \Exception
     */
    protected function doDelete(MassActionHandlerArgs $args)
    {
        $iteration             = 0;
        $entityName            = null;
        $entityIdentifiedField = null;
        $queryBuilder          = $args->getResults()->getSource();

        $results = new DeletionIterableResult($queryBuilder);
        $results->setBufferSize(self::FLUSH_BATCH_SIZE);

        // batch remove should be processed in transaction
        $this->entityManager->beginTransaction();
        try {
            // if huge amount data must be deleted
            set_time_limit(0);

            foreach ($results as $result) {
                /** @var $result ResultRecordInterface */
                $entity = $result->getRootEntity();
                if (!$entity) {
                    // no entity in result record, it should be extracted from DB
                    if (!$entityName) {
                        $entityName = $this->getEntityName($args);
                    }
                    if (!$entityIdentifiedField) {
                        $entityIdentifiedField = $this->getEntityIdentifierField($args);
                    }
                    $entity = $this->getEntity($entityName, $result->getValue($entityIdentifiedField));
                }

                if ($entity) {
                    $this->entityManager->remove($entity);
                    $iteration++;

                    if ($iteration % self::FLUSH_BATCH_SIZE == 0) {
                        $this->finishBatch();
                    }
                }
            }

            if ($iteration % self::FLUSH_BATCH_SIZE > 0) {
                $this->finishBatch();
            }

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return $this->getDeleteResponse($args, $iteration);
    }
}
