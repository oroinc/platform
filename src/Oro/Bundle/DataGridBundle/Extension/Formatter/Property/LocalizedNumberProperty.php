<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter\Property;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

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
    protected function getFormatter()
    {
        return $this->formatter;
    }
}
