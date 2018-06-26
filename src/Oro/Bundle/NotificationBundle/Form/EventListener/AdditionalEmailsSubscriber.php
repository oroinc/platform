<?php

namespace Oro\Bundle\NotificationBundle\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Add additional recipient choices
 */
class AdditionalEmailsSubscriber implements EventSubscriberInterface
{
    const MAX_NESTING_LEVEL = 2;
    const LABEL_GLUE = ' > ';

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ClassMetadata[]
     */
    private $entityMetadataCache = [];

    /**
     * @param ManagerRegistry $registry
     * @param TranslatorInterface $translator
     * @param ConfigManager $configManager
     */
    public function __construct(
        ManagerRegistry $registry,
        TranslatorInterface $translator,
        ConfigManager $configManager
    ) {
        $this->registry = $registry;
        $this->translator = $translator;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT   => 'preSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        /** @var EmailNotification $eventObject */
        $eventObject = $event->getData();

        $entityName = null;
        if (null !== $eventObject && $eventObject->hasEntityName()) {
            $entityName = $eventObject->getEntityName();
        }

        $this->initAdditionalRecipientChoices($entityName, $event->getForm());
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $entityName = null;
        if (!empty($data['entityName'])) {
            $entityName = $data['entityName'];
        }

        $this->initAdditionalRecipientChoices($entityName, $event->getForm());
    }

    /**
     * @param $entityName
     * @param FormInterface $form
     */
    private function initAdditionalRecipientChoices($entityName, FormInterface $form)
    {
        $choices = [];
        if ($entityName !== null) {
            $this->collectEmailFieldsRecursive($entityName, $choices);
        }

        $form->offsetGet('recipientList')->add(
            'additionalEmailAssociations',
            ChoiceType::class,
            [
                'label' => 'oro.notification.emailnotification.additional_email_associations.label',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => $choices,
                'tooltip'     => 'oro.notification.emailnotification.additional_associations.tooltip',
            ]
        );
    }

    /**
     * @param string $entityName
     * @param array $choices
     * @param array $currentPath
     * @param array $currentLabelPath
     */
    private function collectEmailFieldsRecursive($entityName, &$choices, $currentPath = [], $currentLabelPath = [])
    {
        foreach ($this->getEntityMetadata($entityName)->getAssociationMappings() as $fieldName => $mapping) {
            $fieldLabel = $this->getFieldLabel($entityName, $fieldName);

            if (array_key_exists(EmailHolderInterface::class, class_implements($mapping['targetEntity']))) {
                $fieldPath = ($currentPath ? implode('.', $currentPath).'.' : ''). $fieldName;
                $fieldLabelPath =
                    ($currentLabelPath ? implode(self::LABEL_GLUE, $currentLabelPath).self::LABEL_GLUE : '').
                    $fieldLabel;
                $choices[$fieldLabelPath] = $fieldPath;
            }

            if (count($currentPath) < self::MAX_NESTING_LEVEL - 1) {
                $this->collectEmailFieldsRecursive(
                    $mapping['targetEntity'],
                    $choices,
                    array_pad($currentPath, count($currentPath) + 1, $fieldName),
                    array_pad($currentLabelPath, count($currentLabelPath) + 1, $fieldLabel)
                );
            }
        }
    }

    /**
     * @param string $entityName
     * @return ClassMetadata
     */
    private function getEntityMetadata($entityName)
    {
        if (!isset($this->entityMetadataCache[$entityName])) {
            /** @var EntityManager $manager */
            $manager = $this->registry->getManagerForClass($entityName);
            $this->entityMetadataCache[$entityName] = $manager->getClassMetadata($entityName);
        }

        return $this->entityMetadataCache[$entityName];
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @return string|null
     */
    private function getFieldLabel($entityName, $fieldName)
    {
        if (!$this->configManager->hasConfig($entityName, $fieldName)) {
            return $this->prettifyFieldName($fieldName);
        }

        /** @var ConfigProvider $entityProvider */
        $entityConfigProvider = $this->configManager->getProvider('entity');
        $fieldConfig = $entityConfigProvider->getConfig($entityName, $fieldName);

        return $this->translator->trans($fieldConfig->get('label'));
    }

    /**
     * @param string $fieldName
     * @return string
     */
    private function prettifyFieldName($fieldName)
    {
        $fieldLabel = ucfirst($fieldName);
        if (preg_match('/_[a-z0-9]{8}$/', $fieldLabel)) {
            $fieldLabel = preg_replace('/_[a-z0-9]{8}$/', '', $fieldLabel);
        }
        $fieldLabel = str_replace('_', ' ', $fieldLabel);
        $fieldLabel = preg_replace('/([a-z])([A-Z])/', '$1 $2', $fieldLabel);

        return $fieldLabel;
    }
}
