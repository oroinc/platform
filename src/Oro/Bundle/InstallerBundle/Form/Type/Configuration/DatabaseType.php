<?php

namespace Oro\Bundle\InstallerBundle\Form\Type\Configuration;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\InstallerBundle\Validator\Constraints\ExtensionLoaded;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DatabaseType extends AbstractType
{
    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'oro_installer_database_driver',
                ChoiceType::class,
                array(
                    'label'       => 'form.configuration.database.driver',
                    'choices'       => array(
                        DatabaseDriverInterface::DRIVER_MYSQL      => 'MySQL',
                        DatabaseDriverInterface::DRIVER_POSTGRESQL => 'PostgreSQL',
                    ),
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new ExtensionLoaded(),
                    ),
                    'attr' => [
                        'data-mysql-hint' => $this->translator->trans(
                            'form.configuration.database.mysql_hint'
                        ),
                    ]
                )
            )
            ->add(
                'oro_installer_database_host',
                TextType::class,
                array(
                    'label'       => 'form.configuration.database.host',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    ),
                )
            )
            ->add(
                'oro_installer_database_port',
                IntegerType::class,
                array(
                    'label'       => 'form.configuration.database.port',
                    'required'    => false,
                    'constraints' => array(
                        new Assert\Type(array('type' => 'integer')),
                    ),
                )
            )
            ->add(
                'oro_installer_database_name',
                TextType::class,
                array(
                    'label'       => 'form.configuration.database.name',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    ),
                )
            )
            ->add(
                'oro_installer_database_user',
                TextType::class,
                array(
                    'label'       => 'form.configuration.database.user',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    ),
                )
            )
            ->add(
                'oro_installer_database_password',
                PasswordType::class,
                array(
                    'label'    => 'form.configuration.database.password',
                    'required' => false,
                )
            )
            ->add(
                'oro_installer_database_drop',
                ChoiceType::class,
                array(
                    'label'         => 'form.configuration.database.drop',
                    'data'          => 'none',
                    'choices'       => array(
                        'none' => 'form.configuration.database.drop_none',
                        'app'  => 'form.configuration.database.drop_app',
                        'full' => 'form.configuration.database.drop_full'
                    ),
                    'constraints'   => array(
                        new Assert\NotBlank()
                    ),
                )
            );

        $this->addDriversOptionsField($builder);
        $this->addModelTransformber($builder);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_installer_configuration_database';
    }

    /**
     * @param FormBuilderInterface $builder
     */
    private function addDriversOptionsField(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'oro_installer_database_driver_options',
                CollectionType::class,
                [
                    'show_form_when_empty' => false,
                    'handle_primary' => false,
                    'required' => false,
                    'label' => 'form.configuration.database.driver_options.label',
                    'entry_type' => DriverOptionType::class
                ]
            );
    }

    /**
     * @param FormBuilderInterface $builder
     */
    private function addModelTransformber(FormBuilderInterface $builder)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function (array $configurationData) {
                if (!isset($configurationData['oro_installer_database_driver_options'])) {
                    return $configurationData;
                }

                $options = $configurationData['oro_installer_database_driver_options'];
                $collectionArray = [];
                $index = 0;
                foreach ($options as $key => $value) {
                    $collectionArray[$index++] = [
                        'option_key' => $key,
                        'option_value' => $value
                    ];
                }

                $configurationData['oro_installer_database_driver_options'] = $collectionArray;

                return $configurationData;
            },
            function (array $configurationFormData) {
                if (!isset($configurationFormData['oro_installer_database_driver_options'])) {
                    return $configurationFormData;
                }

                $collectionArray = $configurationFormData['oro_installer_database_driver_options'];
                $array = [];
                foreach ($collectionArray as $options) {
                    $array[$options['option_key']] = $options['option_value'];
                }

                $configurationFormData['oro_installer_database_driver_options'] = $array;

                return $configurationFormData;
            }
        ));
    }
}
