<?php

namespace Oro\Bundle\ActionBundle\Action;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Formats the name of an entity using the entity name resolver.
 *
 * This action resolves and formats the display name of an entity object,
 * storing the result in a specified attribute. It leverages the {@see EntityNameResolver}
 * to provide consistent entity naming across the application.
 */
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

    public function __construct(ContextAccessor $contextAccessor, EntityNameResolver $entityNameResolver)
    {
        parent::__construct($contextAccessor);

        $this->entityNameResolver = $entityNameResolver;
    }

    #[\Override]
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

    #[\Override]
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
