<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

abstract class AbstractProcessor implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        switch ($context->getEvent()) {
            case CustomizeFormDataContext::EVENT_PRE_SUBMIT:
                $this->processPreSubmit($context);
                break;
            case CustomizeFormDataContext::EVENT_SUBMIT:
                $this->processSubmit($context);
                break;
            case CustomizeFormDataContext::EVENT_POST_SUBMIT:
                $this->processPostSubmit($context);
                break;
            case CustomizeFormDataContext::EVENT_FINISH_SUBMIT:
                $this->processFinishSubmit($context);
                break;
        }
    }

    /**
     * @param CustomizeFormDataContext $context
     */
    protected function processPreSubmit(CustomizeFormDataContext $context)
    {
    }

    /**
     * @param CustomizeFormDataContext $context
     */
    protected function processSubmit(CustomizeFormDataContext $context)
    {
    }

    /**
     * @param CustomizeFormDataContext $context
     */
    protected function processPostSubmit(CustomizeFormDataContext $context)
    {
    }

    /**
     * @param CustomizeFormDataContext $context
     */
    protected function processFinishSubmit(CustomizeFormDataContext $context)
    {
    }
}
