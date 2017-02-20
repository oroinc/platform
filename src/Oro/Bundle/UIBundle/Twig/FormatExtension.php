<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Component\DependencyInjection\ServiceLink;

class FormatExtension extends \Twig_Extension
{
    /** @var ServiceLink */
    protected $formatterManagerLink;

    /**
     * @param ServiceLink $formatterManagerLink Link is used because of performance reasons
     */
    public function __construct(ServiceLink $formatterManagerLink)
    {
        $this->formatterManagerLink = $formatterManagerLink;
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
        return $this->formatterManagerLink->getService()->format($parameter, $formatterName, $formatterArguments);
    }
}
