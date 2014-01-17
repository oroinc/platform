<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\DeletionIterableResult;

class DeleteMassActionHandler implements MassActionHandlerInterface
{
    const FLUSH_BATCH_SIZE = 100;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $responseMessage = 'oro.grid.mass_action.delete.success_message';

    /**
     * @param EntityManager       $entityManager
     * @param TranslatorInterface $translator
     */
    public function __construct(EntityManager $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $iteration             = 0;
        $entityName            = null;
        $entityIdentifiedField = null;

        $results = new DeletionIterableResult($args->getResults()->getSource());
        $results->setBufferSize(self::FLUSH_BATCH_SIZE);

        // batch remove should be processed in transaction
        $this->entityManager->beginTransaction();
        try {
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
                        $this->entityManager->flush();
                        if ($this->entityManager->getConnection()->getTransactionNestingLevel() == 1) {
                            $this->entityManager->clear();
                        }
                    }
                }
            }

            if ($iteration % self::FLUSH_BATCH_SIZE > 0) {
                $this->entityManager->flush();
                if ($this->entityManager->getConnection()->getTransactionNestingLevel() == 1) {
                    $this->entityManager->clear();
                }
            }
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return $this->getResponse($args, $iteration);
    }

    /**
     * @param MassActionHandlerArgs $args
     * @param int                   $entitiesCount
     *
     * @return MassActionResponse
     */
    protected function getResponse(MassActionHandlerArgs $args, $entitiesCount = 0)
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
     * @param MassActionHandlerArgs $args
     *
     * @return string
     * @throws \LogicException
     */
    protected function getEntityName(MassActionHandlerArgs $args)
    {
        $massAction = $args->getMassAction();
        $entityName = $massAction->getOptions()->offsetGet('entity_name');
        if (!$entityName) {
            throw new \LogicException(sprintf('Mass action "%s" must define entity name', $massAction->getName()));
        }

        return $entityName;
    }

    /**
     * @param MassActionHandlerArgs $args
     *
     * @throws \LogicException
     * @return string
     */
    protected function getEntityIdentifierField(MassActionHandlerArgs $args)
    {
        $massAction = $args->getMassAction();
        $identifier = $massAction->getOptions()->offsetGet('data_identifier');
        if (!$identifier) {
            throw new \LogicException(sprintf('Mass action "%s" must define identifier name', $massAction->getName()));
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
}
