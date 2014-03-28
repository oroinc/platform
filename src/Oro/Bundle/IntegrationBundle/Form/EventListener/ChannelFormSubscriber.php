<?php

namespace Oro\Bundle\IntegrationBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\IntegrationBundle\Entity\Status;
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
            FormEvents::PRE_SET_DATA  => 'preSet',
            FormEvents::POST_SET_DATA => 'postSet',
            FormEvents::PRE_SUBMIT    => 'preSubmit',
        ];
    }

    /**
     * Modifies form based on data that comes from DB
     *
     * @param FormEvent $event
     */
    public function preSet(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var Channel $data */
        $data = $event->getData();

        if ($data === null) {
            return;
        }

        $this->muteFields($form, $data);

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

        $data->setType($type);
        $event->setData($data);
    }

    /**
     * Set not mapped field
     *
     * @param FormEvent $event
     */
    public function postSet(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var Channel $data */
        $data = $event->getData();

        if ($data === null) {
            return;
        }

        $typeChoices = array_keys($form->get('transportType')->getConfig()->getOption('choices'));
        $firstChoice = reset($typeChoices);
        if ($transport = $data->getTransport()) {
            $transportType = $this->registry->getTransportTypeBySettingEntity($transport, $data->getType(), true);
        } else {
            $transportType = $firstChoice;
        }
        $form->get('transportType')->setData($transportType);

        // populate empty transport type in case when default values from empty entity should be mapped to form
        if (!$transport = $data->getTransport()) {
            $transport = $this->registry->getTransportType($form->get('type')->getData(), $transportType)
                ->getSettingsEntityFQCN();
            if (class_exists($transport)) {
                $form->get('transport')->setData(new $transport);
            }
        }
    }

    /**
     * Modifies form based on submitted data
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var Channel $originalData */
        $originalData = $form->getData();
        $data         = $event->getData();

        $this->muteFields($form, $originalData);

        if (!empty($data['type'])) {
            $type                  = $data['type'];
            $transportTypeModifier = $this->getTransportTypeModifierClosure($type);
            $transportTypeModifier($form);

            $connectorsModifier = $this->getConnectorsModifierClosure($type);
            $connectorsModifier($form);

            // value that was set on postSet is replaced by null from request
            $typeChoices           = array_keys($form->get('transportType')->getConfig()->getOption('choices'));
            $firstChoice           = reset($typeChoices);
            $data['transportType'] = isset($data['transportType'])
                ? $data['transportType'] : $firstChoice;

            /*
             * If transport type changed we have to modify ViewData(it's already saved entity)
             * due to it's not matched the 'data_class' option of newly added form type
             */
            if ($transport = $originalData->getTransport()) {
                $transportType = $this->registry->getTransportTypeBySettingEntity(
                    $transport,
                    $originalData->getType(),
                    true
                );
                // second condition cover case when we have same name for few channel types
                if ($transportType !== $data['transportType'] || $originalData->getType() !== $data['type']) {
                    /** @var Channel $setEntity */
                    $setEntity = $form->getViewData();
                    $setEntity->clearTransport();
                }
            }

            $transportModifier = $this->getTransportModifierClosure($type, $data['transportType']);
            $transportModifier($form);

            $event->setData($data);
        }
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

            $choices = $registry->getAvailableTransportTypesChoiceList($type);
            FormUtils::replaceField($form, 'transportType', ['choices' => $choices], ['choice_list']);
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

            $choices = $registry->getAvailableConnectorsTypesChoiceList($type);
            FormUtils::replaceField($form, 'connectors', ['choices' => $choices], ['choice_list']);
        };
    }

    /**
     * Returns closure that adds transport field dependent on the rest form data
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

            $connectorsKey = 'connectors';
            $children      = $form->getIterator();

            $form->add('transport', $formType, ['required' => true]);

            // re-add connectors to the end of list
            if (isset($children[$connectorsKey])) {
                $connectors = $children[$connectorsKey];
                unset($children[$connectorsKey]);
                $children[$connectorsKey] = $connectors;
            }
        };
    }

    /**
     * Disable fields that are not allowed to be modified since channel has at least one sync completed
     *
     * @param FormInterface $form
     * @param Channel       $channel
     */
    protected function muteFields(FormInterface $form, Channel $channel = null)
    {
        if (!($channel && $channel->getId())) {
            // do nothing if channel is new
            return;
        }

        if (static::wasChannelSynced($channel)) {
            // disable type field
            FormUtils::replaceField($form, 'type', ['disabled' => true]);
        }
    }

    /**
     * Return true if channel was synced at least once
     *
     * @param Channel $channel
     *
     * @return bool
     */
    public static function wasChannelSynced(Channel $channel)
    {
        return $channel->getStatuses()->exists(
            function ($key, Status $status) {
                return intval($status->getCode()) === Status::STATUS_COMPLETED;
            }
        );
    }
}
