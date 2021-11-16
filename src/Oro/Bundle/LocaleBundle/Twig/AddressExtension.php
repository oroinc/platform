<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides a Twig function for postal address formatting:
 *   - oro_format_address
 *   - oro_format_address_html
 */
class AddressExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private const ADDRESS_TEMPLATE = '@OroLocale/Twig/address.html.twig';

    /** @var ContainerInterface */
    protected $container;

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
            ),
            new TwigFilter(
                'oro_format_address_html',
                [$this, 'formatAddressHtml'],
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
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
     * Formats address using twig template.
     * Wraps each address part into a tag.
     * @throws \Throwable
     */
    public function formatAddressHtml(
        Environment $environment,
        AddressInterface $address,
        ?string $country = null,
        string $newLineSeparator = "\n"
    ): string {
        $addressFormatter = $this->getAddressFormatter();
        $country = $country ?: $addressFormatter->getCountry($address);
        $addressFormat = $addressFormatter->getAddressFormat($country);
        $parts = $addressFormatter->getAddressParts($address, $addressFormat, $country);

        $template = $environment->load(self::ADDRESS_TEMPLATE);
        foreach ($parts as $partKey => $partValue) {
            $partName = strtolower(trim($partKey, '%'));
            $partBlockName = 'address_part_' . $partName;
            $blockName = $template->hasBlock($partBlockName, []) ? $partBlockName : 'address_part';
            $parts[$partKey] = $template->renderBlock(
                $blockName,
                ['part_name' => $partName, 'part_value' => $partValue]
            );
        }

        $formatted = str_replace(array_keys($parts), array_values($parts), $addressFormat);
        $formatted = preg_replace(
            ['/ +/', '/(?:\\\\n)+/', '/ +\n/'],
            [' ', $newLineSeparator, $newLineSeparator],
            $formatted
        );
        $formatted = nl2br(trim($formatted, ' ' . $newLineSeparator));

        return $template->renderBlock('address', ['formatted' => $formatted]);
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
