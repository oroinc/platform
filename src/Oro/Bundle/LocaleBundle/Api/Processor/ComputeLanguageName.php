<?php

namespace Oro\Bundle\LocaleBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of the "name" field for Language entity.
 */
class ComputeLanguageName implements ProcessorInterface
{
    private LanguageCodeFormatter $formatter;

    public function __construct(LanguageCodeFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $nameFieldName = $context->getResultFieldName('name');
        if ($context->isFieldRequested($nameFieldName, $data)) {
            $data[$nameFieldName] = $this->formatter->formatLocale($context->getResultFieldValue('code', $data));
            $context->setData($data);
        }
    }
}
