<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\State;

class EntityStateManager
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * Store entities to set new states. Array key - is new state
     *
     * @var array[]
     */
    protected $newEntities = [];

    /**
     * Entities to reset
     *
     * @var object[]
     */
    protected $resetEntities = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Set state to objects
     *
     * @param object[] $entities
     * @param int $state
     */
    public function setState(array $entities, $state)
    {
        if (array_key_exists($state, $this->newEntities) && is_array($this->newEntities[$state])) {
            $this->newEntities[$state] = array_merge($this->newEntities[$state], $entities);
        } else {
            $this->newEntities[$state] = $entities;
        }
    }

    /**
     * Reset state to objects
     *
     * @param array $entities
     */
    public function resetState(array $entities)
    {
        $this->resetEntities = array_merge($this->resetEntities, $entities);
    }

    /**
     * Flush states
     */
    public function flush()
    {
        if (count($this->newEntities) > 0) {
            $this->flushNewEntities();
        }
        if (count($this->resetEntities) > 0) {
            $this->flushResetEntities();
        }

        $this->clear();
    }

    /**
     * Clear entities
     */
    public function clear()
    {
        $this->newEntities = [];
        $this->resetEntities = [];
    }

    /**
     * Save new state entities to db
     */
    protected function flushNewEntities()
    {
        $em = $this->doctrineHelper->getEntityManager(State::class);
        $repository = $em->getRepository(State::class);

        foreach ($this->newEntities as $state => $entities) {
            foreach ($entities as $entity) {
                $identifier = $this->doctrineHelper->getSingleEntityIdentifier($entity);

                if ($identifier) {
                    $className = $this->doctrineHelper->getEntityClass($entity);
                    $affectedRows = $repository->createQueryBuilder('st')
                        ->update()
                        ->where('st.entityClass = :entityClass AND st.entityId = :entityId')
                        ->set('st.state', ':state')
                        ->getQuery()
                        ->execute(
                            [
                                'entityClass' => $className,
                                'entityId'    => $identifier,
                                'state'       => $state,
                            ]
                        );


                    if ($affectedRows === 0) {
                        /** @var State $stateEntity */
                        $stateEntity = $this->doctrineHelper
                            ->createEntityInstance(State::class);
                        $stateEntity->setEntityClass($className)
                            ->setEntityId($identifier)
                            ->setState($state);
                        $em->persist($stateEntity);
                        $em->flush([$stateEntity]);
                    }
                }
            }
        }
    }

    /**
     * Remove reset entities from db
     */
    protected function flushResetEntities()
    {
        $em = $this->doctrineHelper->getEntityManager(State::class);
        $repository = $em->getRepository(State::class);

        foreach ($this->resetEntities as $entity) {
            $identifier = $this->doctrineHelper->getSingleEntityIdentifier($entity);

            if ($identifier) {
                $className = $this->doctrineHelper->getEntityClass($entity);

                $repository
                    ->createQueryBuilder('st')
                    ->delete()
                    ->where('st.entityClass = :entityClass AND st.entityId = :entityId')
                    ->getQuery()
                    ->execute([
                        'entityClass' => $className,
                        'entityId'    => $identifier
                    ]);
            }
        }
    }
}
