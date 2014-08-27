<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\ExecutionContext;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueEnumName;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumNameType extends AbstractType
{
    const INVALID_NAME_MESSAGE =
        'This value should contain only alphabetic symbols, underscore, hyphen, spaces and numbers.';

    /** @var ConfigManager */
    protected $configManager;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /**
     * @param ConfigManager                   $configManager
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     */
    public function __construct(
        ConfigManager $configManager,
        ExtendDbIdentifierNameGenerator $nameGenerator
    ) {
        $this->configManager = $configManager;
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
                    new NotBlank()
                ]
            )
        );

        $constraintsNormalizer = function (Options $options, $constraints) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $options['config_id'];
            $enumCode      = $this->getEnumCode($fieldConfigId);
            if (empty($enumCode)) {
                // validations of new enum
                $constraints[] = new Length(['max' => $this->nameGenerator->getMaxEnumCodeSize()]);
                $constraints[] = new Regex(
                    [
                        'pattern' => '/^[\w- ]*$/',
                        'message' => self::INVALID_NAME_MESSAGE
                    ]
                );
                $constraints[] = new Callback(
                    [
                        function ($value, ExecutionContext $context) {
                            if (!empty($value)) {
                                $code = ExtendHelper::buildEnumCode($value, false);
                                if (empty($code)) {
                                    $context->addViolation(self::INVALID_NAME_MESSAGE, ['{{ value }}' => $value]);
                                }
                            }
                        }
                    ]
                );
                $constraints[] = new UniqueEnumName(
                    [
                        'entityClassName' => $fieldConfigId->getClassName(),
                        'fieldName'       => $fieldConfigId->getFieldName()
                    ]
                );
            } else {
                // validations of existing enum
                $constraints[] = new Length(['max' => 255]);
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
        $configId = $options['config_id'];
        if (!($configId instanceof FieldConfigId)) {
            return false;
        }

        $className = $configId->getClassName();
        if (empty($className)) {
            return false;
        }

        $fieldName = $configId->getFieldName();

        // disable for new field that reuses a public enum
        if ($options['config_is_new']) {
            $enumConfigProvider = $this->configManager->getProvider('enum');
            if ($enumConfigProvider->hasConfig($className, $fieldName)) {
                $enumFieldConfig = $enumConfigProvider->getConfig($className, $fieldName);
                $enumCode        = $enumFieldConfig->get('enum_code');
                if (!empty($enumCode)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Tries to get an enum code from configs of the given field
     *
     * @param FieldConfigId $fieldConfigId
     *
     * @return string|null
     */
    protected function getEnumCode(FieldConfigId $fieldConfigId)
    {
        $enumCode = null;

        $enumConfigProvider = $this->configManager->getProvider('enum');
        if ($enumConfigProvider->hasConfig($fieldConfigId->getClassName(), $fieldConfigId->getFieldName())) {
            $enumCode = $enumConfigProvider
                ->getConfig($fieldConfigId->getClassName(), $fieldConfigId->getFieldName())
                ->get('enum_code');
        }

        return $enumCode;
    }
}
