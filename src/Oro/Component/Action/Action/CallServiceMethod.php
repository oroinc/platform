<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

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

        if (empty($options['method'])) {
            throw new InvalidParameterException('Method name parameter is required');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidParameterException
     */
    protected function executeAction($context)
    {
        $service = $this->getService($context);
        $method = $this->getMethod();

        if (!method_exists($service, $method)) {
            throw new InvalidParameterException(
                sprintf('Could not found public method "%s" in service "%s"', $method, $this->options['service'])
            );
        }

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
     * @param mixed $context
     *
     * @return object
     *
     * @throws InvalidParameterException
     */
    protected function getService($context)
    {
        $service = $this->contextAccessor->getValue($context, $this->options['service']);

        if (!$this->container->has($service)) {
            throw new InvalidParameterException(sprintf('Undefined service with name "%s"', $service));
        }

        return $this->container->get($service);
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

        array_walk_recursive($parameters, function (&$value) use ($context) {
            $value = $this->contextAccessor->getValue($context, $value);
        });

        return $parameters;
    }
}
