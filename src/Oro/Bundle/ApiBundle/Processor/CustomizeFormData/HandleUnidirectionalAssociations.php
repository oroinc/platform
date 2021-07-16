<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Form\Handler\UnidirectionalAssociationHandler;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\UnidirectionalAssociationCompleter;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Handles all fields that represent unidirectional associations for "create" and "update" actions.
 */
class HandleUnidirectionalAssociations implements ProcessorInterface
{
    /** @var UnidirectionalAssociationHandler */
    private $handler;

    public function __construct(UnidirectionalAssociationHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        $config = $context->getConfig();
        if (null === $config) {
            // not supported API resource
            return;
        }

        $unidirectionalAssociations = $config->get(
            UnidirectionalAssociationCompleter::UNIDIRECTIONAL_ASSOCIATIONS
        );
        if (empty($unidirectionalAssociations)) {
            // there are no unidirectional associations
            return;
        }

        $this->handler->handleUpdate(
            $context->getForm(),
            $config,
            $unidirectionalAssociations,
            $context->getRequestType()
        );
    }
}
