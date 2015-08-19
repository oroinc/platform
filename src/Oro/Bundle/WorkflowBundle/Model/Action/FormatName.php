<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class FormatName extends AbstractAction
{
    /**
     * @var EntityNameResolver
     */
    protected $entityNameResolver;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param ContextAccessor $contextAccessor
     * @param EntityNameResolver $entityNameResolver
     */
    public function __construct(ContextAccessor $contextAccessor, EntityNameResolver $entityNameResolver)
    {
        parent::__construct($contextAccessor);

        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }
        if (empty($options['object'])) {
            throw new InvalidParameterException('Object parameter is required');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $this->contextAccessor->setValue(
            $context,
            $this->options['attribute'],
            $this->entityNameResolver->getName(
                $this->contextAccessor->getValue($context, $this->options['object'])
            )
        );
    }
}
