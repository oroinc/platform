<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks if there are any errors in the context or contexts of batch items,
 * and if so, localizes all properties that are represented by the Label object.
 */
class NormalizeErrors implements ProcessorInterface
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        $this->normalizeErrors($context->getErrors());
        $items = $context->getBatchItems();
        if ($items) {
            foreach ($items as $item) {
                $this->normalizeErrors($item->getContext()->getErrors());
            }
        }
    }

    /**
     * @param Error[] $errors
     */
    private function normalizeErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $error->trans($this->translator);
        }
    }
}
