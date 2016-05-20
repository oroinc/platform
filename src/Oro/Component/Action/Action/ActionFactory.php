<?php

namespace Oro\Component\Action\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\ConfigExpression\ExpressionInterface;

class ActionFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $types;

    /**
     * @param ContainerInterface $container
     * @param array $types
     */
    public function __construct(ContainerInterface $container, array $types = array())
    {
        $this->container = $container;
        $this->types = $types;
    }

    /**
     * @param string $type
     * @param array $options
     * @param ExpressionInterface $condition
     * @throws \RunTimeException
     * @return ActionInterface
     */
    public function create($type, array $options = array(), ExpressionInterface $condition = null)
    {
        if (!$type) {
            throw new \RuntimeException('The action type must be defined');
        }

        $id = isset($this->types[$type]) ? $this->types[$type] : false;

        if (!$id) {
            throw new \RuntimeException(sprintf('No attached service to action type named `%s`', $type));
        }

        $action = $this->container->get($id);

        if (!$action instanceof ActionInterface) {
            throw new \RuntimeException(sprintf('The service `%s` must implement `ActionInterface`', $id));
        }

        $action->initialize($options);

        if ($condition) {
            $action->setCondition($condition);
        }

        return $action;
    }
}
