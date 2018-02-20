<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig\Rest;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\AbstractAddStatusCodes;
use Oro\Component\ChainProcessor\ContextInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractCompleteStatusCodes extends AbstractAddStatusCodes
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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

    /**
     * @param StatusCodesConfig $statusCodes
     * @param string|null       $targetAction
     */
    protected function addStatusCodes(StatusCodesConfig $statusCodes, $targetAction)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'Returned when an unexpected error occurs'
        );
    }
}
