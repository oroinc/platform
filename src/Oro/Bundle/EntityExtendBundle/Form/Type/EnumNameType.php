<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueEnumName;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumNameType extends AbstractType
{
    const INVALID_NAME_MESSAGE =
        'This value should contain only alphabetic symbols, underscore, hyphen, spaces and numbers.';

    /** @var EnumTypeHelper */
    protected $typeHelper;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /**
     * @param EnumTypeHelper                  $typeHelper
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     */
    public function __construct(
        EnumTypeHelper $typeHelper,
        ExtendDbIdentifierNameGenerator $nameGenerator
    ) {
        $this->typeHelper    = $typeHelper;
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'constraints' => [
                    new Assert\NotBlank()
                ]
            )
        );

        $constraintsNormalizer = function (Options $options, $constraints) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $options['config_id'];
            if (!$this->typeHelper->hasEnumCode($fieldConfigId->getClassName(), $fieldConfigId->getFieldName())) {
                // validations of new enum
                $constraints[] = new Assert\Length(['max' => $this->nameGenerator->getMaxEnumCodeSize()]);
                $constraints[] = new Assert\Regex(
                    [
                        'pattern' => '/^[\w- ]*$/',
                        'message' => self::INVALID_NAME_MESSAGE
                    ]
                );
                $callback = function ($value, ExecutionContextInterface $context) {
                    if (!empty($value)) {
                        $code = ExtendHelper::buildEnumCode($value, false);
                        if (empty($code)) {
                            $context->addViolation(self::INVALID_NAME_MESSAGE, ['{{ value }}' => $value]);
                        }
                    }
                };
                $constraints[] = new Assert\Callback([$callback]);
                $constraints[] = new UniqueEnumName(
                    [
                        'entityClassName' => $fieldConfigId->getClassName(),
                        'fieldName'       => $fieldConfigId->getFieldName()
                    ]
                );
            } else {
                // validations of existing enum
                $constraints[] = new Assert\Length(['max' => 255]);
            }

            return $constraints;
        };

        $resolver->setNormalizers(
            [
                'constraints'       => $constraintsNormalizer,
                'disabled'          => function (Options $options, $value) {
                    return $this->isReadOnly($options) ? true : $value;
                },
                'validation_groups' => function (Options $options, $value) {
                    return $options['disabled'] ? false : $value;
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_entity_extend_enum_name';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'text';
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

        // check if new field reuses a public enum
        if ($options['config_is_new'] && $this->typeHelper->hasEnumCode($className, $fieldName)) {
            return true;
        }

        return false;
    }
}
