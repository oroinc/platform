<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\Handler\UnidirectionalAssociationHandler;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\UnidirectionalAssociationCompleter;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Handles all fields that represent unidirectional associations for "create" and "update" actions.
 */
class HandleUnidirectionalAssociations implements ProcessorInterface
{
    private UnidirectionalAssociationHandler $handler;

    public function __construct(UnidirectionalAssociationHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $config = $context->getConfig();
        if (null === $config) {
            // not supported API resource
            return;
        }

        $unidirectionalAssociations = $this->getUnidirectionalAssociations($config);
        if (!$unidirectionalAssociations) {
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

    private function getUnidirectionalAssociations(EntityDefinitionConfig $config): ?array
    {
        $associations = $config->get(UnidirectionalAssociationCompleter::UNIDIRECTIONAL_ASSOCIATIONS);
        if (!$associations) {
            return null;
        }

        $readonlyFields = $config->get(UnidirectionalAssociationCompleter::UNIDIRECTIONAL_ASSOCIATIONS_READONLY);
        if ($readonlyFields) {
            foreach ($readonlyFields as $name) {
                unset($associations[$name]);
            }
        }
        if (!$associations) {
            return null;
        }

        return $associations;
    }
}
