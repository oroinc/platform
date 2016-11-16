<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Form\Guesser\InverseAssociationTypeGuesser;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Shared\SwitchFormExtension;
use Oro\Bundle\ApiBundle\Processor\Subresource\ContextParentConfigAccessor;
use Oro\Bundle\ApiBundle\Processor\Subresource\ContextParentMetadataAccessor;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;

/**
 * Switches to Data API form extension.
 */
class InitializeApiFormExtension extends SwitchFormExtension implements ProcessorInterface
{

    /** @var InverseAssociationTypeGuesser */
    protected $inverseMetadataTypeGuesser;

    /**
     * @param FormExtensionSwitcherInterface $formExtensionSwitcher
     * @param MetadataTypeGuesser            $metadataTypeGuesser
     * @param InverseAssociationTypeGuesser  $inverseAssociationTypeGuesser
     */
    public function __construct(
        FormExtensionSwitcherInterface $formExtensionSwitcher,
        MetadataTypeGuesser $metadataTypeGuesser,
        InverseAssociationTypeGuesser $inverseAssociationTypeGuesser
    ) {
        $this->formExtensionSwitcher = $formExtensionSwitcher;
        $this->metadataTypeGuesser = $metadataTypeGuesser;
        $this->inverseMetadataTypeGuesser = $inverseAssociationTypeGuesser;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext|FormContext $context */

        if ($this->isApiFormExtensionActivated($context)) {
            // the API form extension is already activated
            return;
        }

        $this->switchToApiFormExtension($context);
        $this->rememberContext($context);
        $this->metadataTypeGuesser->setIncludedEntities($context->getIncludedEntities());
        $this->metadataTypeGuesser->setMetadataAccessor(new ContextParentMetadataAccessor($context));
        $this->metadataTypeGuesser->setConfigAccessor(new ContextParentConfigAccessor($context));

        $this->inverseMetadataTypeGuesser->setConfigAccessor(new ContextParentConfigAccessor($context));
    }
}
