<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * The form that we use when creating additional fields for entities. It has restrictions for integer
 * types that are necessary because there are architectural restrictions, and there must be a certain size on the
 * value that can be used in the platform.
 */
class FixedIntegerType extends AbstractType
{
    // 2 bytes, small-range integer, valid values: -32768 to +32767
    private const SMALLINT = 'smallint';

    // 4 bytes, usual choice for integer: valid values: -2147483648 to +2147483647
    private const INTEGER = 'integer';

    // 8 bytes, large-range integer, valid values: -9223372036854775808 to 9223372036854775807
    private const BIGINT = 'bigint';

    /**
     * Please note that there are restrictions for bigint on the frontend.
     * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Number/MAX_SAFE_INTEGER.
     * MAX_SAFE_INTEGER = 9007199254740991
     */
    private const NUMBERS = [
        self::SMALLINT => ['min' => -32768, 'max' => 32767],
        self::INTEGER => ['min' => -2147483648, 'max' => 2147483647],
        self::BIGINT => ['min' => -9007199254740991, 'max' => 9007199254740991] # JS support (MAX safe integers).
    ];

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('data_type')
            ->required()
            ->allowedTypes('string')
            ->allowedValues(self::SMALLINT, self::INTEGER, self::BIGINT);

        $resolver->setDefaults([
            'constraints' => fn (Options $options) => [new Range(self::NUMBERS[$options['data_type']])]
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return IntegerType::class;
    }
}
