<?php

namespace Oro\Bundle\DraftBundle\Form\Extension;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Exclude oneToMany relation type from FieldType(ExtendFieldConfig) form
 */
class ExtendFieldTypeExtension extends AbstractTypeExtension
{
    /**
     * @var array
     */
    private $excludeTypes = [];

    /**
     * @param array $excludeTypes
     */
    public function __construct($excludeTypes = [])
    {
        $this->excludeTypes = $excludeTypes;
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['class_name', 'excludeTypes']);
        $resolver->setNormalizer('excludeTypes', function (Options $options, $value) {
            $className = $options->offsetGet('class_name');

            return $this->isSupport($className) ? array_merge($value, $this->excludeTypes) : $value;
        });
    }

    /**
     * @inheritDoc
     */
    public static function getExtendedTypes(): array
    {
        return [FieldType::class];
    }

    private function isSupport(string $className): bool
    {
        return is_a($className, DraftableInterface::class, true);
    }
}
