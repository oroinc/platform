<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\UIBundle\Formatter\FormatterManager;

class FormatExtension extends \Twig_Extension
{
    /** @var FormatterManager */
    protected $formatterManager;

    /**
     * @param FormatterManager $formatterManager
     */
    public function __construct(FormatterManager $formatterManager)
    {
        $this->formatterManager = $formatterManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('oro_format', [$this, 'format'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_formatter_extension';
    }

    /**
     * @param mixed  $parameter
     * @param string $formatterName
     * @param array  $formatterArguments
     *
     * @return mixed
     */
    public function format($parameter, $formatterName, array $formatterArguments = [])
    {
        return $this->formatterManager->format($parameter, $formatterName, $formatterArguments);
    }
}
