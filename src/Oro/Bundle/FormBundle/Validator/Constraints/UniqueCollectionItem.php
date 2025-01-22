<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint can be used to check that a collection has only unique items.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class UniqueCollectionItem extends Constraint
{
    public $message = 'This collection should contain only unique elements.';
    public string $collection;
    public array $fields;

    #[\Override]
    public function getRequiredOptions(): array
    {
        return ['fields', 'collection'];
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
