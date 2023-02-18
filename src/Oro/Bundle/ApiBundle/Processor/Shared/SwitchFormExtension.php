<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Form\FormExtensionSwitcherInterface;
use Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesser;
use Oro\Bundle\ApiBundle\Processor\FormContext;

/**
 * The base class for processors that switch to form extensions.
 */
abstract class SwitchFormExtension
{
    private const API_FORM_EXTENSION_ACTIVATED = 'apiFormExtensionActivated';

    private const PREVIOUS_METADATA_ACCESSOR = 'previousMetadataAccessor';
    private const PREVIOUS_CONFIG_ACCESSOR = 'previousConfigAccessor';
    private const PREVIOUS_ENTITY_MAPPER = 'previousEntityMapper';
    private const PREVIOUS_INCLUDED_ENTITIES = 'previousIncludedEntities';

    protected FormExtensionSwitcherInterface $formExtensionSwitcher;
    protected MetadataTypeGuesser $metadataTypeGuesser;

    public function __construct(
        FormExtensionSwitcherInterface $formExtensionSwitcher,
        MetadataTypeGuesser $metadataTypeGuesser
    ) {
        $this->formExtensionSwitcher = $formExtensionSwitcher;
        $this->metadataTypeGuesser = $metadataTypeGuesser;
    }

    protected function isApiFormExtensionActivated(FormContext $context): bool
    {
        return (bool)$context->get(self::API_FORM_EXTENSION_ACTIVATED);
    }

    protected function switchToApiFormExtension(FormContext $context): void
    {
        $this->formExtensionSwitcher->switchToApiFormExtension();
        $context->set(self::API_FORM_EXTENSION_ACTIVATED, true);
    }

    protected function switchToDefaultFormExtension(FormContext $context): void
    {
        $this->formExtensionSwitcher->switchToDefaultFormExtension();
        $context->remove(self::API_FORM_EXTENSION_ACTIVATED);
    }

    protected function rememberContext(FormContext $context): void
    {
        // remember current metadata type guesser context as an action can be nested
        // and this context should be restored when the current action is finished
        $this->rememberValue(
            $context,
            self::PREVIOUS_ENTITY_MAPPER,
            $this->metadataTypeGuesser->getEntityMapper()
        );
        $this->rememberValue(
            $context,
            self::PREVIOUS_INCLUDED_ENTITIES,
            $this->metadataTypeGuesser->getIncludedEntities()
        );
        $this->rememberValue(
            $context,
            self::PREVIOUS_METADATA_ACCESSOR,
            $this->metadataTypeGuesser->getMetadataAccessor()
        );
        $this->rememberValue(
            $context,
            self::PREVIOUS_CONFIG_ACCESSOR,
            $this->metadataTypeGuesser->getConfigAccessor()
        );
    }

    protected function restoreContext(FormContext $context): void
    {
        $this->metadataTypeGuesser->setEntityMapper($context->get(self::PREVIOUS_ENTITY_MAPPER));
        $this->metadataTypeGuesser->setIncludedEntities($context->get(self::PREVIOUS_INCLUDED_ENTITIES));
        $this->metadataTypeGuesser->setMetadataAccessor($context->get(self::PREVIOUS_METADATA_ACCESSOR));
        $this->metadataTypeGuesser->setConfigAccessor($context->get(self::PREVIOUS_CONFIG_ACCESSOR));

        $context->remove(self::PREVIOUS_ENTITY_MAPPER);
        $context->remove(self::PREVIOUS_INCLUDED_ENTITIES);
        $context->remove(self::PREVIOUS_METADATA_ACCESSOR);
        $context->remove(self::PREVIOUS_CONFIG_ACCESSOR);
    }

    protected function rememberValue(FormContext $context, string $contextKey, mixed $currentValue): void
    {
        if (null !== $currentValue) {
            $context->set($contextKey, $currentValue);
        }
    }
}
