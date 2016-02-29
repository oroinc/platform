<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;

class FlushEntity extends AbstractAction
{
    const OPTION_KEY_ENTITY = 'entity';

    /**
     * @var array
     */
    protected $options;

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
    public function initialize(array $options)
    {
        if (count($options)) {
            throw new InvalidParameterException('Options not supported');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $entity = $context->getEntity();

        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass(ClassUtils::getClass($entity));
        $entityManager->persist($entity);
        $entityManager->flush($entity);
    }
}
