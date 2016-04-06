<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;

/**
 * Submits the request data to the form.
 */
class SubmitForm implements ProcessorInterface
{

    /**
     * @var bool Whether to set fields to NULL
     *           when they are missing in the
     *           submitted data.
     */
    protected $clearMissing;

    /**
     * @param bool $clearMissing
     */
    public function __construct($clearMissing = false)
    {
        $this->clearMissing = $clearMissing;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        if (!$context->hasForm()) {
            // no form
            return;
        }

        $form = $context->getForm();
        if ($form->isSubmitted()) {
            // the form is already submitted
            return;
        }

        $form->submit($context->getRequestData(), $this->clearMissing);
    }
}
