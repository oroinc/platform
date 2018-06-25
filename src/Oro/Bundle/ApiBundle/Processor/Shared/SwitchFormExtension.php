<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Form\FormExtensionSwitcherInterface;
use Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesser;
use Oro\Bundle\ApiBundle\Processor\FormContext;

abstract class SwitchFormExtension
{
    const API_FORM_EXTENSION_ACTIVATED = 'apiFormExtensionActivated';

    const PREVIOUS_METADATA_ACCESSOR  = 'previousMetadataAccessor';
    const PREVIOUS_CONFIG_ACCESSOR    = 'previousConfigAccessor';
    const PREVIOUS_ENTITY_MAPPER      = 'previousEntityMapper';
    const PREVIOUS_INCLUDED_ENTITIES  = 'previousIncludedEntities';

    /** @var FormExtensionSwitcherInterface */
    protected $formExtensionSwitcher;

    /** @var MetadataTypeGuesser */
    protected $metadataTypeGuesser;

    /**
     * @param FormExtensionSwitcherInterface $formExtensionSwitcher
     * @param MetadataTypeGuesser            $metadataTypeGuesser
     */
    public function __construct(
        FormExtensionSwitcherInterface $formExtensionSwitcher,
        MetadataTypeGuesser $metadataTypeGuesser
    ) {
        $this->formExtensionSwitcher = $formExtensionSwitcher;
        $this->metadataTypeGuesser = $metadataTypeGuesser;
    }

    /**
     * @param FormContext $context
     *
     * @return bool
     */
    protected function isApiFormExtensionActivated(FormContext $context)
    {
        return (bool)$context->get(self::API_FORM_EXTENSION_ACTIVATED);
    }

    /**
     * @param FormContext $context
     */
    protected function switchToApiFormExtension(FormContext $context)
    {
        $this->formExtensionSwitcher->switchToApiFormExtension();
        $context->set(self::API_FORM_EXTENSION_ACTIVATED, true);
    }

    /**
     * @param FormContext $context
     */
    protected function switchToDefaultFormExtension(FormContext $context)
    {
        $this->formExtensionSwitcher->switchToDefaultFormExtension();
        $context->remove(self::API_FORM_EXTENSION_ACTIVATED);
    }

    /**
     * @param FormContext $context
     */
    protected function rememberContext(FormContext $context)
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

    /**
     * @param FormContext $context
     */
    protected function restoreContext(FormContext $context)
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

    /**
     * @param FormContext $context
     * @param string      $contextKey
     * @param mixed       $currentValue
     */
    protected function rememberValue(FormContext $context, $contextKey, $currentValue)
    {
        if (null !== $currentValue) {
            $context->set($contextKey, $currentValue);
        }
    }
}
