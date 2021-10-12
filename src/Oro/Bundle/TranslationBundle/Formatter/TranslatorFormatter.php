<?php

namespace Oro\Bundle\TranslationBundle\Formatter;

use Oro\Bundle\UIBundle\Formatter\FormatterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Formats translation messages in email templates.
 */
class TranslatorFormatter implements FormatterInterface
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function format($value, array $formatterArguments = []): string
    {
        return $this->translator->trans($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue(): string
    {
        return $this->translator->trans('N/A');
    }
}
