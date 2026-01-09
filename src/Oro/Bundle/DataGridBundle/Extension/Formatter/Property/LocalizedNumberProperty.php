<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter\Property;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Formats numeric values according to locale settings in datagrid columns.
 *
 * This property formatter uses the NumberFormatter to display numbers with appropriate
 * decimal separators, thousand separators, and precision based on the user's locale.
 */
class LocalizedNumberProperty extends AbstractLocalizedProperty
{
    /** @var $formatter */
    protected $formatter;

    public function __construct(TranslatorInterface $translator, NumberFormatter $formatter)
    {
        parent::__construct($translator);
        $this->formatter = $formatter;
    }

    /**
     * @return NumberFormatter
     */
    #[\Override]
    protected function getFormatter()
    {
        return $this->formatter;
    }
}
