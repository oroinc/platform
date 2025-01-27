<?php

declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Twig\Environment as TwigEnvironment;

/**
 * Formats an address using a TWIG template.
 */
class FormattedAddressRenderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private string $templateName = '@OroLocale/Twig/address.html.twig';

    public function __construct(
        private TwigEnvironment $twigEnvironment,
        private bool $isDebug
    ) {
        $this->logger = new NullLogger();
    }

    public function setTemplateName(string $templateName): void
    {
        $this->templateName = $templateName;
    }

    /**
     * Formats an address using a TWIG template.
     * Wraps each address part into the corresponding tag.
     *
     * @param array<string,string|array{value: string, is_html_safe: bool}> $addressParts Address parts, e.g. fetched
     *  from {@see AddressFormatter}::getAddressParts(). Example:
     *      [
     *          '%country%' => 'US',
     *          '%region%' => ['value' => '<i>CA</i>', 'is_html_safe' => true],
     *          // ...
     *      ]
     * @param string $addressFormat Address format, e.g. fetched from {@see AddressFormatter}::getAddressFormat.
     *  Example:
     *      %name%\n%organization%\n%street%\n%CITY% %REGION_CODE% %COUNTRY_ISO2% %postal_code%\n%phone%
     *
     * @param string $newLineSeparator
     *
     * @return string Formatted address rendered via TWIG.
     */
    public function renderAddress(
        array $addressParts,
        string $addressFormat,
        string $newLineSeparator = "\n"
    ): string {
        try {
            $template = $this->twigEnvironment->load($this->templateName);
            $renderedParts = [];
            foreach ($addressParts as $partKey => $partValue) {
                $isHtmlSafe = false;
                if (is_array($partValue)) {
                    $isHtmlSafe = $partValue['is_html_safe'] ?? false;
                    $partValue = $partValue['value'] ?? '';
                }

                $partName = strtolower(trim($partKey, '%'));
                $partBlockName = 'address_part_' . $partName;
                $blockName = $template->hasBlock($partBlockName) ? $partBlockName : 'address_part';
                $renderedParts[$partKey] = $template->renderBlock(
                    $blockName,
                    ['part_name' => $partName, 'part_value' => $partValue, 'is_html_safe' => $isHtmlSafe]
                );
            }

            $formatted = str_replace(array_keys($renderedParts), array_values($renderedParts), $addressFormat);
            $formatted = preg_replace(
                ['/ +/', '/(?:\\\\n)+/', '/ +\n/'],
                [' ', $newLineSeparator, $newLineSeparator],
                $formatted
            );
            $formatted = nl2br(trim($formatted, ' ' . $newLineSeparator));

            return $template->renderBlock('address', ['formatted' => $formatted]);
        } catch (\Throwable $throwable) {
            $this->logger->error('Rendering of an address failed: {message}', [
                'message' => $throwable->getMessage(),
                'addressParts' => $addressParts,
                'addressFormat' => $addressFormat,
                'throwable' => $throwable,
            ]);

            if ($this->isDebug) {
                return sprintf('Rendering of an address failed: %s', $throwable->getMessage());
            }

            return '';
        }
    }
}
