<?php

namespace Oro\Bundle\EntityBundle\Formatter;

use Oro\Bundle\UIBundle\Formatter\FormatterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The formatter for "simple_array" data type.
 * The list of supported parameters:
 * * separator - A separator between elements. Defaults to ", ".
 * * translatable - Whether elements should be localized of not. Defaults to FALSE.
 * * translation_domain - The translation domain. Defaults to NULL.
 * * translation_template - The translation label template for each element, e.g. "acme.statuses.%s". Defaults to "%s".
 */
class SimpleArrayFormatter implements FormatterInterface
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function format($value, array $formatterArguments = [])
    {
        if (!$value) {
            return $this->getDefaultValue();
        }

        if (isset($formatterArguments['translatable']) && $formatterArguments['translatable']) {
            $domain = $formatterArguments['translation_domain'] ?? null;
            $template = $formatterArguments['translation_template'] ?? null;
            $items = [];
            foreach ($value as $item) {
                if ($template) {
                    $item = sprintf($template, $item);
                }
                $items[] = $this->translator->trans((string) $item, [], $domain);
            }
            $value = $items;
        }

        return implode($formatterArguments['separator'] ?? ', ', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return $this->translator->trans('oro.entity.formatter.simple_array.default');
    }
}
