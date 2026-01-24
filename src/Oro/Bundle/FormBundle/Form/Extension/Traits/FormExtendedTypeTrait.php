<?php

namespace Oro\Bundle\FormBundle\Form\Extension\Traits;

use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * Provides a reusable implementation for form type extensions.
 *
 * This trait simplifies the creation of form type extensions by providing the
 * {@see FormExtendedTypeTrait::getExtendedTypes()} method, which declares that the extension applies to the
 * base FormType. Classes using this trait should implement the extension logic in their own methods.
 */
trait FormExtendedTypeTrait
{
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
