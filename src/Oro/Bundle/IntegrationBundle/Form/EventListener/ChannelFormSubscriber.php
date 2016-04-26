<?php

namespace Oro\Bundle\IntegrationBundle\Form\EventListener;

use Doctrine\Common\Util\Inflector;

use Oro\Bundle\IntegrationBundle\Utils\EditModeUtils;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\SettingsProvider;
use Oro\Bundle\IntegrationBundle\Form\Type\IntegrationSettingsDynamicFormType;

class ChannelFormSubscriber implements EventSubscriberInterface
{
    /** @var TypesRegistry */
    protected $registry;

    /** @var SettingsProvider */
    protected $settingsProvider;

    /**
     * @param TypesRegistry    $registry
     * @param SettingsProvider $settingsProvider
     */
    public function __construct(TypesRegistry $registry, SettingsProvider $settingsProvider)
    {
        $this->registry         = $registry;
        $this->settingsProvider = $settingsProvider;
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
        /** @var Integration $data */
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

        $synchronizationSettingsModifier = $this->getDynamicModifierClosure($type, 'synchronization_settings');
        $synchronizationSettingsModifier($form);

        $mappingSettingsModifier = $this->getDynamicModifierClosure($type, 'mapping_settings');
        $mappingSettingsModifier($form);

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
        /** @var Integration $data */
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

        $integrationType = $form->get('type')->getData();

        // populate empty transport type in case when default values from empty entity should be mapped to form
        if ($integrationType && !$transport = $data->getTransport()) {
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

        /** @var Integration $originalData */
        $originalData = $form->getData();
        $data         = $event->getData();

        $this->muteFields($form, $originalData);

        if (!empty($data['type'])) {
            $type                  = $data['type'];
            $transportTypeModifier = $this->getTransportTypeModifierClosure($type);
            $transportTypeModifier($form);

            $connectorsModifier = $this->getConnectorsModifierClosure($type);
            $connectorsModifier($form);

            $synchronizationSettingsModifier = $this->getDynamicModifierClosure($type, 'synchronization_settings');
            $synchronizationSettingsModifier($form);

            $mappingSettingsModifier = $this->getDynamicModifierClosure($type, 'mapping_settings');
            $mappingSettingsModifier($form);

            // value that was set on postSet is replaced by null from request
            $typeChoices           = array_keys($form->get('transportType')->getConfig()->getOption('choices'));
            $firstChoice           = reset($typeChoices);
            $data['transportType'] = isset($data['transportType'])
                ? $data['transportType'] : $firstChoice;

            /*
             * If transport type changed we have to modify ViewData(it's already saved entity)
             * due to it's not matched the 'data_class' option of newly added form type
             */
            if (($originalData !== null) && $transport = $originalData->getTransport()) {
                $transportType = $this->registry->getTransportTypeBySettingEntity(
                    $transport,
                    $originalData->getType(),
                    true
                );
                // second condition cover case when we have same name for few integration types
                if ($transportType !== $data['transportType'] || $originalData->getType() !== $data['type']) {
                    /** @var Integration $setEntity */
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
     * Returns closure that fills transport type choices depends on selected integration type
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
     * Returns closure that fills connectors choices depends on selected integration type
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
     * @param string $integrationType
     * @param string $transportType
     *
     * @return callable
     */
    protected function getTransportModifierClosure($integrationType, $transportType)
    {
        $registry = $this->registry;

        return function (FormInterface $form) use ($integrationType, $transportType, $registry) {
            if (!($integrationType && $transportType)) {
                return;
            }

            $formType = $registry->getTransportType($integrationType, $transportType)->getSettingsFormType();

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
     * @param string $type
     * @param string $formName
     *
     * @return callable
     */
    protected function getDynamicModifierClosure($type, $formName)
    {
        $settingsProvider = $this->settingsProvider;

        return function (FormInterface $form) use ($type, $settingsProvider, $formName) {
            if (!$type) {
                return;
            }

            $fields = $settingsProvider->getFormSettings($formName, $type);
            if ($fields) {
                $form->add(Inflector::camelize($formName), new IntegrationSettingsDynamicFormType($fields));
            }
        };
    }

    /**
     * Disable fields that are not allowed to be modified since integration has at least one sync completed
     *
     * @param FormInterface $form
     * @param Integration       $integration
     */
    protected function muteFields(FormInterface $form, Integration $integration = null)
    {
        if (!($integration && $integration->getId())) {
            // do nothing if integration is new
            return;
        }

        $editMode = $integration->getEditMode();

        if (!EditModeUtils::isEditAllowed($editMode)) {
            // disable type and connectors field for all integrations except with edit_mode allow
            FormUtils::replaceField($form, 'type', ['disabled' => true]);
            FormUtils::replaceField($form, 'connectors', ['disabled' => true, 'attr' => ['class' => 'hide']]);
        }


        if ($integration->getId()) {
            // disable enabled field for not new integrations
            FormUtils::replaceField($form, 'enabled', ['disabled' => true, 'attr' => ['class' => 'hide']]);
        }
    }
}
