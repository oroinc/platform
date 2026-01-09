<?php

namespace Oro\Bundle\SearchBundle\Utils;

use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides operator choices for full-text search filtering.
 *
 * This utility class provides translated operator choices for filtering search results
 * using full-text search operations. It supports "contains" and "does not contain" operators
 * with proper translation support for user-friendly display.
 */
class SearchAllText
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public function getOperatorChoices()
    {
        return [
            $this->translator->trans('oro.filter.form.label_type_contains') => TextFilterType::TYPE_CONTAINS ,
            $this->translator->trans('oro.filter.form.label_type_not_contains') => TextFilterType::TYPE_NOT_CONTAINS,
        ];
    }
}
