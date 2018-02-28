<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AddressExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return AddressFormatter
     */
    protected function getAddressFormatter()
    {
        return $this->container->get('oro_locale.formatter.address');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_format_address',
                [$this, 'formatAddress'],
                ['is_safe' => ['html']]
            )
        ];
    }

    /**
     * Formats address according to locale settings.
     *
     * @param AddressInterface $address
     * @param string|null      $country
     * @param string           $newLineSeparator
     *
     * @return string
     */
    public function formatAddress(AddressInterface $address, $country = null, $newLineSeparator = "\n")
    {
        return $this->getAddressFormatter()->format($address, $country, $newLineSeparator);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_locale_address';
    }
}
