<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides common functionality for multi-choice filter form types.
 *
 * This base class extends {@see AbstractChoiceType} to support filters that allow multiple values to be selected.
 * Subclasses should implement specific multi-choice filter types for different data types and use cases.
 */
abstract class AbstractMultiChoiceType extends AbstractChoiceType
{
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct($translator);
    }
}
