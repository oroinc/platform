<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;

/**
 * Transforms and validates the request data via the form from the Context.
 */
class SubmitForm implements ProcessorInterface
{
    /** @var bool */
    protected $clearMissing;

    /**
     * @param bool $clearMissing Whether to set fields to NULL when they are missing in the submitted data.
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

        $form->submit($this->prepareRequestData($context->getRequestData()), $this->clearMissing);
    }

    /**
     * @param array $requestData
     *
     * @return array
     */
    protected function prepareRequestData(array $requestData)
    {
        /**
         * as Symfony Form treats false as NULL due to checkboxes
         * @see Symfony\Component\Form\Form::submit
         * we have to convert false to its string representation here
         */
        array_walk(
            $requestData,
            function (&$value) {
                if (false === $value) {
                    $value = 'false';
                }
            }
        );

        return $requestData;
    }
}
