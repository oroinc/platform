<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\WorkflowBundle\Exception\ActionException;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class CreateEntity extends CreateObject
{
    const OPTION_KEY_FLUSH = 'flush';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ManagerRegistry $registry
     */
    public function __construct(ContextAccessor $contextAccessor, ManagerRegistry $registry)
    {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    protected function createObject($context)
    {
        $entityClassName = $this->getObjectClassName();

        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityClassName);
        if (!$entityManager) {
            throw new NotManageableEntityException($entityClassName);
        }

        $entity = parent::createObject($context);

        try {
            $entityManager->persist($entity);
            if ($this->doFlush()) {
                $entityManager->flush($entity);
            }
        } catch (\Exception $e) {
            throw new ActionException(
                sprintf('Can\'t create entity %s. %s', $entityClassName, $e->getMessage())
            );
        }

        return $entity;
    }

    /**
     * Whether perform flush immediately after entity creation or later
     *
     * @return bool
     */
    protected function doFlush()
    {
        return $this->getOption($this->options, self::OPTION_KEY_FLUSH, false);
    }
}
