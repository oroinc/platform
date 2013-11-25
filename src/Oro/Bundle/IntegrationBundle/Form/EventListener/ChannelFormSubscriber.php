<?php

namespace Oro\Bundle\IntegrationBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;

class ChannelFormSubscriber implements EventSubscriberInterface
{
    /** @var TypesRegistry */
    protected $registry;

    public function __construct(TypesRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSet',
            FormEvents::PRE_SUBMIT   => 'preSubmit'
        ];
    }

    public function preSet(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var Channel $data */
        $data = $event->getData();

        if ($data === null) {
            return;
        }

        $typeChoices = array_keys($form->get('type')->getConfig()->getOption('choices'));
        $firstChoice = reset($typeChoices);

        $type                  = $data->getType() ? : $firstChoice;
        $transportTypeModifier = $this->getTransportTypeModifierClosure($type);
        $transportTypeModifier($form);

        $connectorsModifier = $this->getConnectorsModifierClosure($type);
        $connectorsModifier($form);

        $typeChoices = array_keys($form->get('transportType')->getConfig()->getOption('choices'));
        $firstChoice = reset($typeChoices);
        if ($transport = $data->getTransport()) {
            $transportType = $this->registry->getTransportTypeBySettingEntity($transport, $type, true);
        } else {
            $transportType = $firstChoice;
        }
        $transportModifier = $this->getTransportModifierClosure($type, $transportType);
        $transportModifier($form);

        $form->get('transportType')->setData($transportType);
        $data->setType($type);
        $event->setData($data);
    }

    public function preSubmit(FormEvent $event)
    {

    }

    /**
     * Returns closure that fills transport type choices depends on selected channel type
     *
     * @param string $type
     *
     * @return callable
     */
    protected function getTransportTypeModifierClosure($type)
    {
        $registry = $this->registry;

        return function (FormInterface $form) use ($type, $registry) {
            if (!$type) {
                return;
            }

            if ($form->has('transportType')) {
                $config = $form->get('transportType')->getConfig()->getOptions();
                unset($config['choice_list']);
                unset($config['choices']);
            } else {
                $config = array();
            }

            if (array_key_exists('auto_initialize', $config)) {
                $config['auto_initialize'] = false;
            }

            $choices = $registry->getAvailableTransportTypesChoiceList($type);

            $form->add('transportType', 'choice', array_merge($config, ['choices' => $choices]));
        };
    }

    /**
     * Returns closure that fills connectors choices depends on selected channel type
     *
     * @param string $type
     *
     * @return callable
     */
    protected function getConnectorsModifierClosure($type)
    {
        $registry = $this->registry;

        return function (FormInterface $form) use ($type, $registry) {
            if (!$type) {
                return;
            }

            if ($form->has('connectors')) {
                $config = $form->get('connectors')->getConfig()->getOptions();
                unset($config['choice_list']);
                unset($config['choices']);
            } else {
                $config = array();
            }

            if (array_key_exists('auto_initialize', $config)) {
                $config['auto_initialize'] = false;
            }

            $choices = $registry->getAvailableConnectorsTypesChoiceList($type);

            $form->add('connectors', 'choice', array_merge($config, ['choices' => $choices]));
        };
    }

    /**
     * Returns closure that adds transport field dependent on rest form data
     *
     * @param string $channelType
     * @param string $transportType
     *
     * @return callable
     */
    protected function getTransportModifierClosure($channelType, $transportType)
    {
        $registry = $this->registry;

        return function (FormInterface $form) use ($channelType, $transportType, $registry) {
            if (!($channelType && $transportType)) {
                return;
            }

            $formType = $registry->getTransportType($channelType, $transportType)->getSettingsFormType();
            $form->add('transport', $formType);
        };
    }
}
