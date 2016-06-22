<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig\Rest;

use Symfony\Component\HttpFoundation\Response;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodeConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

abstract class AbstractCompleteStatusCodes implements ProcessorInterface
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

    /**
     * @param StatusCodesConfig $statusCodes
     * @param int               $statusCode
     * @param string            $message
     */
    protected function addStatusCode(StatusCodesConfig $statusCodes, $statusCode, $message)
    {
        if (!$statusCodes->hasCode($statusCode)) {
            $statusCodes->addCode($statusCode, $this->createStatusCode($message));
        }
    }

    /**
     * @param string $description
     *
     * @return StatusCodeConfig
     */
    protected function createStatusCode($description)
    {
        $code = new StatusCodeConfig();
        $code->setDescription($description);

        return $code;
    }
}
