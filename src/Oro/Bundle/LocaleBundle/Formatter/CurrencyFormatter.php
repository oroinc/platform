<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Oro\Bundle\UIBundle\Formatter\FormatterInterface;

class CurrencyFormatter implements FormatterInterface
{
    /** @var NumberFormatter */
    protected $formatter;

    /**
     * @param NumberFormatter $formatter
     */
    public function __construct(NumberFormatter $formatter)
    {
        $this->formatter = $formatter;
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
        $currency = $this->getOption($formatterArguments, 'currency');
        $attributes = (array)$this->getOption($formatterArguments, 'attributes', []);
        $textAttributes = (array)$this->getOption($formatterArguments, 'textAttributes', []);
        $symbols = (array)$this->getOption($formatterArguments, 'symbols', []);
        $locale = $this->getOption($formatterArguments, 'locale');

        return $this->formatter->formatCurrency(
            $parameter,
            $currency,
            $attributes,
            $textAttributes,
            $symbols,
            $locale
        );
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

    /**
     * Gets option or default value if option not exist
     *
     * @param array $options
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getOption(array $options, $name, $default = null)
    {
        return isset($options[$name]) ? $options[$name] : $default;
    }
}
