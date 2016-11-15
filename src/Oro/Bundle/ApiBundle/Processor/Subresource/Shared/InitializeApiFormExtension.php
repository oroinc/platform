<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Form\Guesser\InverseAssociationTypeGuesser;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Form\FormExtensionSwitcherInterface;
use Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesser;
use Oro\Bundle\ApiBundle\Processor\Subresource\ContextParentConfigAccessor;
use Oro\Bundle\ApiBundle\Processor\Subresource\ContextParentMetadataAccessor;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;

/**
 * Switches to Data API form extension.
 */
class InitializeApiFormExtension implements ProcessorInterface
{
    /** @var FormExtensionSwitcherInterface */
    protected $formExtensionSwitcher;

    /** @var MetadataTypeGuesser */
    protected $metadataTypeGuesser;

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
        /** @var SubresourceContext $context */

        $this->formExtensionSwitcher->switchToApiFormExtension();
        $this->metadataTypeGuesser->setMetadataAccessor(new ContextParentMetadataAccessor($context));
        $this->metadataTypeGuesser->setConfigAccessor(new ContextParentConfigAccessor($context));

        $this->inverseMetadataTypeGuesser->setConfigAccessor(new ContextParentConfigAccessor($context));
    }
}
