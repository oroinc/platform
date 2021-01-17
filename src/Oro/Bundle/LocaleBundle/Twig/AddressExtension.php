<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides a Twig function for postal address formatting:
 *   - oro_format_address
 */
class AddressExtension extends AbstractExtension implements ServiceSubscriberInterface
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
            new TwigFilter(
                'oro_format_address',
                [$this, 'formatAddress']
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

    /**
     * {@inheritdoc]
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_locale.formatter.address' => AddressFormatter::class
        ];
    }
}
