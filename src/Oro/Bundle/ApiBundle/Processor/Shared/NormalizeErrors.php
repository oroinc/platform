<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Checks if there are any errors in the Context,
 * and if so, localizes all properties that are represented by the Label object.
 */
class NormalizeErrors implements ProcessorInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if (!$context->hasErrors()) {
            // no errors
            return;
        }

        $errors = $context->getErrors();
        foreach ($errors as $error) {
            $error->trans($this->translator);
        }
    }
}
