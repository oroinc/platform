<?php

namespace Oro\Bundle\InstallerBundle\Form\Type\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class WebsocketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'oro_installer_websocket_bind_address',
                'text',
                array(
                    'label'         => 'form.configuration.websocket.bind.address',
                    'required'      => false,
                    'attr' => [
                        'title' => 'form.configuration.websocket.bind.address.tooltip'
                    ],
                )
            )
            ->add(
                'oro_installer_websocket_bind_port',
                'integer',
                array(
                    'label'         => 'form.configuration.websocket.bind.port',
                    'required'      => false,
                    'constraints'   => array(
                        new Assert\Type(array('type' => 'integer')),
                    ),
                    'attr' => [
                        'title' => 'form.configuration.websocket.bind.port.tooltip'
                    ]
                )
            )
            ->add(
                'oro_installer_websocket_backend_host',
                'text',
                array(
                    'label'         => 'form.configuration.websocket.backend.host',
                    'required'      => false,
                    'attr' => [
                        'title' => 'form.configuration.websocket.backend.host.tooltip'
                    ],
                )
            )
            ->add(
                'oro_installer_websocket_backend_port',
                'integer',
                array(
                    'label'         => 'form.configuration.websocket.backend.port',
                    'required'      => false,
                    'constraints'   => array(
                        new Assert\Type(array('type' => 'integer')),
                    ),
                    'attr' => [
                        'title' => 'form.configuration.websocket.backend.port.tooltip'
                    ],
                )
            )
            ->add(
                'oro_installer_websocket_frontend_host',
                'text',
                array(
                    'label'         => 'form.configuration.websocket.frontend.host',
                    'required'      => false,
                    'attr' => [
                        'title' => 'form.configuration.websocket.frontend.host.tooltip'
                    ],
                )
            )
            ->add(
                'oro_installer_websocket_frontend_port',
                'integer',
                array(
                    'label'         => 'form.configuration.websocket.frontend.port',
                    'required'      => false,
                    'constraints'   => array(
                        new Assert\Type(array('type' => 'integer')),
                    ),
                    'attr' => [
                        'title' => 'form.configuration.websocket.frontend.port.tooltip'
                    ],
                )
            );
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
}
