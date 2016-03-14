<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Oro\Bundle\LocaleBundle\Twig\NumberExtension;
use Oro\Bundle\UIBundle\Formatter\FormatterInterface;

class CurrencyFormatter implements FormatterInterface
{
    /**
     * @var NumberExtension
     */
    protected $numberExtension;

    /**
     * @param NumberExtension $numberExtension
     */
    public function __construct(NumberExtension $numberExtension)
    {
        $this->numberExtension = $numberExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatterName()
    {
        return 'currency';
    }

    /**
     * {@inheritdoc}
     */
    public function format($parameter, array $formatterArguments = [])
    {
        return $this->numberExtension->formatCurrency($parameter, $formatterArguments);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedTypes()
    {
        return ['money'];
    }

    /**
     * {@inheritdoc}
     */
    public function isDefaultFormatter()
    {
        return true;
    }
}
