<?php

namespace Oro\Bundle\InstallerBundle\Form\Type\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class WebsocketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addBindFields($builder);
        $this->addBackendFields($builder);
        $this->addFrontendFields($builder);
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
        return 'oro_installer_configuration_websocket';
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addBindFields(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'oro_installer_websocket_bind_address',
                TextType::class,
                [
                    'label' => 'form.configuration.websocket.bind.address',
                    'required' => false,
                    'attr' => [
                        'title' => 'form.configuration.websocket.bind.address.tooltip'
                    ],
                ]
            )
            ->add(
                'oro_installer_websocket_bind_port',
                IntegerType::class,
                [
                    'label' => 'form.configuration.websocket.bind.port',
                    'required' => false,
                    'constraints' => [
                        new Assert\Type(['type' => 'integer']),
                    ],
                    'attr' => [
                        'title' => 'form.configuration.websocket.bind.port.tooltip'
                    ]
                ]
            );
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addBackendFields(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'oro_installer_websocket_backend_host',
                TextType::class,
                [
                    'label' => 'form.configuration.websocket.backend.host',
                    'required' => false,
                    'attr' => [
                        'title' => 'form.configuration.websocket.backend.host.tooltip'
                    ],
                ]
            )
            ->add(
                'oro_installer_websocket_backend_port',
                IntegerType::class,
                [
                    'label' => 'form.configuration.websocket.backend.port',
                    'required' => false,
                    'constraints' => [
                        new Assert\Type(['type' => 'integer']),
                    ],
                    'attr' => [
                        'title' => 'form.configuration.websocket.backend.port.tooltip'
                    ],
                ]
            )
            ->add(
                'oro_installer_websocket_backend_path',
                TextType::class,
                [
                    'label' => 'form.configuration.websocket.backend.path',
                    'required' => false,
                    'constraints' => [
                        new Assert\Type(['type' => 'string']),
                    ],
                    'attr' => [
                        'title' => 'form.configuration.websocket.backend.path.tooltip'
                    ],
                ]
            );
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addFrontendFields(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'oro_installer_websocket_frontend_host',
                TextType::class,
                [
                    'label' => 'form.configuration.websocket.frontend.host',
                    'required' => false,
                    'attr' => [
                        'title' => 'form.configuration.websocket.frontend.host.tooltip'
                    ],
                ]
            )
            ->add(
                'oro_installer_websocket_frontend_port',
                IntegerType::class,
                [
                    'label' => 'form.configuration.websocket.frontend.port',
                    'required' => false,
                    'constraints' => [
                        new Assert\Type(['type' => 'integer']),
                    ],
                    'attr' => [
                        'title' => 'form.configuration.websocket.frontend.port.tooltip'
                    ],
                ]
            )
            ->add(
                'oro_installer_websocket_frontend_path',
                TextType::class,
                [
                    'label' => 'form.configuration.websocket.frontend.path',
                    'required' => false,
                    'constraints' => [
                        new Assert\Type(['type' => 'string']),
                    ],
                    'attr' => [
                        'title' => 'form.configuration.websocket.frontend.path.tooltip'
                    ],
                ]
            );
    }
}
