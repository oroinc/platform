<?php

namespace Oro\Bundle\SecurityBundle\Action;

use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Generates uuid and sets to the specified attribute.
 *
 * Usage:
 *
 * @generate_uuid:
 *      attribute: $.result.uuid
 */
class GenerateUuidAction extends AbstractAction
{
    /** @var string|PropertyPathInterface */
    private $attributePath;

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context): void
    {
        $this->contextAccessor->setValue($context, $this->attributePath, UUIDGenerator::v4());
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options): self
    {
        if (empty($options)) {
            throw new InvalidParameterException(sprintf('Parameter "%s" is required', 'attribute'));
        }

        $this->attributePath = reset($options);

        return $this;
    }
}
