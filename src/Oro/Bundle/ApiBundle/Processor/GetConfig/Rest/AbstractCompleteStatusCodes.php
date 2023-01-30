<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\Rest;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\AbstractAddStatusCodes;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * The base class for processors that add possible HTTP status codes to the entity configuration.
 */
abstract class AbstractCompleteStatusCodes extends AbstractAddStatusCodes
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (null === $definition) {
            $definition = new EntityDefinitionConfig();
            $context->setResult($definition);
        }
        $statusCodes = $definition->getStatusCodes();
        if (null === $statusCodes) {
            $statusCodes = new StatusCodesConfig();
            $context->getResult()->setStatusCodes($statusCodes);
        }
        $this->addStatusCodes($statusCodes, $context->getTargetAction());
    }

    protected function addStatusCodes(StatusCodesConfig $statusCodes, ?string $targetAction): void
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'Returned when an unexpected error occurs'
        );
    }
}
