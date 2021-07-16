<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Behat\Stub\Form\Type;

use Oro\Bundle\IntegrationBundle\Entity\Stub\TestTransport2Settings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class TestTransport2SettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_test_transport_2_settings';

    /**
     * @throws ConstraintDefinitionException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'transport2Field',
                TextType::class,
                [
                    'label' => 'Transport 2 Field',
                ]
            );
    }

    /**
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TestTransport2Settings::class,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
