<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Makes "createdAt" and "updatedAt" fields read-only.
 */
class MakeTimestampableFieldsReadOnly implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll() || !$definition->hasFields()) {
            // expected completed config
            return;
        }

        if (!$this->doctrineHelper->isManageableEntityClass($context->getClassName())) {
            // only manageable entities are supported
            return;
        }

        $this->makeFieldReadOnly($definition, 'createdAt');
        $this->makeFieldReadOnly($definition, 'updatedAt');
    }

    private function makeFieldReadOnly(EntityDefinitionConfig $definition, string $fieldName): void
    {
        $field = $definition->getField($fieldName);
        if (null !== $field && !$field->isExcluded()) {
            $formOptions = $field->getFormOptions();
            if (null === $formOptions || !\array_key_exists('mapped', $formOptions)) {
                $formOptions['mapped'] = false;
                $field->setFormOptions($formOptions);
            }
        }
    }
}
