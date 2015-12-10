<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class CallServiceMethod extends AbstractAction
{
    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    protected $options;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ContainerInterface $container
     */
    public function __construct(ContextAccessor $contextAccessor, ContainerInterface $container)
    {
        parent::__construct($contextAccessor);
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['service'])) {
            throw new InvalidParameterException('Service name parameter is required');
        }
        if (!$this->container->has($options['service'])) {
            throw new InvalidParameterException(sprintf('Undefined service with name "%s"', $options['service']));
        }
        if (empty($options['method'])) {
            throw new InvalidParameterException('Method name parameter is required');
        }
        $this->options = $options;
        if (!method_exists($this->getService(), $this->getMethod())) {
            throw new InvalidParameterException(
                sprintf('Could not found public method "%s" in service "%s"', $this->getMethod(), $options['service'])
            );
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $service = $this->getService();
        $method = $this->getMethod();
        $callback = [$service, $method];
        $parameters = $this->getMethodParameters($context);
        $result = call_user_func_array($callback, $parameters);
        $attribute = $this->getAttribute();
        if ($attribute) {
            $this->contextAccessor->setValue($context, $attribute, $result);
        }
    }

    /**
     * @return PropertyPathInterface|null
     */
    protected function getAttribute()
    {
        return $this->getOption($this->options, 'attribute', null);
    }

    /**
     * @return object
     */
    protected function getService()
    {
        return $this->container->get($this->options['service']);
    }

    /**
     * @return string
     */
    protected function getMethod()
    {
        return $this->options['method'];
    }

    /**
     * @param mixed $context
     * @return array
     */
    protected function getMethodParameters($context)
    {
        $parameters = $this->getOption($this->options, 'method_parameters', []);
        foreach ($parameters as $name => $value) {
            $parameters[$name] = $this->contextAccessor->getValue($context, $value);
        }
        return $parameters;
    }
}
