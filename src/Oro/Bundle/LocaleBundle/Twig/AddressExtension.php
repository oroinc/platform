<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides the TWIG filters to format the address according to locale settings:
 *   - oro_format_address - Formats address according to locale settings.
 *   - oro_format_address_html - Formats address using twig template. Wraps each address part into a tag.
 */
class AddressExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('oro_format_address', $this->formatAddress(...)),
            new TwigFilter('oro_format_address_html', $this->formatAddressHtml(...), ['is_safe' => ['html']])
        ];
    }

    public function formatAddress(
        AddressInterface $address,
        ?string $country = null,
        string $newLineSeparator = "\n"
    ): string {
        return $this->getAddressFormatter()->format($address, $country, $newLineSeparator);
    }

    public function formatAddressHtml(
        AddressInterface $address,
        ?string $country = null,
        string $newLineSeparator = "\n"
    ): string {
        $addressFormatter = $this->getAddressFormatter();
        $country = $country ?: $addressFormatter->getCountry($address);
        $addressFormat = $addressFormatter->getAddressFormat($country);
        $parts = $addressFormatter->getAddressParts($address, $addressFormat, $country);

        return $this->getFormattedAddressRenderer()->renderAddress($parts, $addressFormat, $newLineSeparator);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            AddressFormatter::class,
            FormattedAddressRenderer::class
        ];
    }

    private function getAddressFormatter(): AddressFormatter
    {
        return $this->container->get(AddressFormatter::class);
    }

    private function getFormattedAddressRenderer(): FormattedAddressRenderer
    {
        return $this->container->get(FormattedAddressRenderer::class);
    }
}
