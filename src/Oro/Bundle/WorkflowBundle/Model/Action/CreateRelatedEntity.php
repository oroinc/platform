<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

use Oro\Component\Action\Model\ContextAccessor;
use Oro\Component\Action\Action\AbstractAction as ComponentAbstractAction;
use Oro\Component\Action\Action\CreateObject as ComponentCreateObject;
use Oro\Component\Action\Exception\ActionException;
use Oro\Component\Action\Exception\InvalidParameterException;

/**
 * Class CreateRelatedEntity.
 *
 * Create workflow entity and set it to corresponding property of context
 */
class CreateRelatedEntity extends ComponentAbstractAction
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @param ContextAccessor $contextAccessor
     * @param ManagerRegistry $registry
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        ManagerRegistry $registry
    ) {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        if (!$context instanceof WorkflowItem) {
            throw new \InvalidArgumentException('Context must be instance of WorkflowItem');
        }

        $entityClassName = $context->getDefinition()->getRelatedEntity();
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityClassName);
        if (!$entityManager) {
            throw new NotManageableEntityException($entityClassName);
        }

        $entity = $context->getEntity();
        $data = $this->getData();
        if (is_array($data) && !empty($data)) {
            $this->assignObjectData($context, $entity, $data);
        }
        try {
            $entityManager->persist($entity);
            $entityManager->flush($entity);
        } catch (\Exception $e) {
            throw new ActionException(
                sprintf('Can\'t create related entity %s. %s', $entityClassName, $e->getMessage())
            );
        }
    }

    /**
     * @return array()
     */
    protected function getData()
    {
        return $this->getOption($this->options, ComponentCreateObject::OPTION_KEY_DATA, array());
    }

    /**
     * @param mixed $context
     * @param object $entity
     * @param array $parameters
     */
    protected function assignObjectData($context, $entity, array $parameters)
    {
        foreach ($parameters as $parameterName => $valuePath) {
            $parameterValue = $this->contextAccessor->getValue($context, $valuePath);
            $this->contextAccessor->setValue($entity, $parameterName, $parameterValue);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!empty($options[ComponentCreateObject::OPTION_KEY_DATA]) &&
            !is_array($options[ComponentCreateObject::OPTION_KEY_DATA])
        ) {
            throw new InvalidParameterException('Object data must be an array.');
        }
        $this->options = $options;

        return $this;
    }
}
