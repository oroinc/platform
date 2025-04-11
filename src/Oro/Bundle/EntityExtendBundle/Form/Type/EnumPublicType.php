<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Enum public form type.
 */
class EnumPublicType extends AbstractType
{
    /** @var EnumTypeHelper */
    protected $typeHelper;

    public function __construct(EnumTypeHelper $typeHelper)
    {
        $this->typeHelper = $typeHelper;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizer(
            'disabled',
            function (Options $options, $value) {
                return $this->isReadOnly($options) ? true : $value;
            }
        )->setNormalizer(
            'validation_groups',
            function (Options $options, $value) {
                return $options['disabled'] ? false : $value;
            }
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_entity_extend_enum_public';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    /**
     * Checks if the form type should be read-only or not
     *
     * @param Options $options
     *
     * @return bool
     */
    protected function isReadOnly($options)
    {
        /** @var ConfigIdInterface $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();

        if (empty($className)) {
            return false;
        }

        $fieldName = $this->typeHelper->getFieldName($configId);
        if (empty($fieldName)) {
            return false;
        }

        if ($this->typeHelper->isSystem($className, $fieldName)) {
            // it is a system field
            return true;
        }

        $enumCode = $this->typeHelper->getEnumCode($className, $fieldName);
        if (!empty($enumCode)) {
            if ($options['config_is_new']) {
                // a new field reuses public enum
                return true;
            }
            if ($this->typeHelper->isImmutable('enum', $className, $fieldName, 'public')) {
                // is immutable
                return true;
            }
            if ($this->typeHelper->hasOtherReferences($enumCode, $className, $fieldName)) {
                // an enum is reused by other fields
                return true;
            }
        }

        return false;
    }
}
