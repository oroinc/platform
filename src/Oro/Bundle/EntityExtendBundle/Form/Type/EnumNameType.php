<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueEnumName;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

class EnumNameType extends AbstractType
{
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
                        'message' => 'This value should contains only alphabetic symbols,'
                            . ' numbers, spaces, underscore or minus symbols'
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
            array(
                'constraints' => $constraintsNormalizer
            )
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
