<?php

namespace Oro\Bundle\NotificationBundle\Form\EventListener;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Provider\ChainAdditionalEmailAssociationProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Adds additional recipients field to the email notification form.
 */
class AdditionalEmailsSubscriber implements EventSubscriberInterface
{
    private const MAX_NESTING_LEVEL = 2;
    private const LABEL_GLUE        = ' > ';

    /** @var ChainAdditionalEmailAssociationProvider */
    private $associationProvider;

    public function __construct(ChainAdditionalEmailAssociationProvider $associationProvider)
    {
        $this->associationProvider = $associationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT   => 'preSubmit'
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        /** @var EmailNotification|null $notification */
        $notification = $event->getData();

        $entityName = null;
        if (null !== $notification && $notification->hasEntityName()) {
            $entityName = $notification->getEntityName();
        }

        $this->initAdditionalRecipientChoices($entityName, $event->getForm());
    }

    public function preSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $entityName = null;
        if (!empty($data['entityName'])) {
            $entityName = $data['entityName'];
        }

        $this->initAdditionalRecipientChoices($entityName, $event->getForm());
    }

    private function initAdditionalRecipientChoices(?string $entityName, FormInterface $form): void
    {
        $choices = [];
        if (null !== $entityName) {
            $this->collectEmailFieldsRecursive($entityName, $choices);
        }

        $form->offsetGet('recipientList')->add(
            'additionalEmailAssociations',
            ChoiceType::class,
            [
                'label'    => 'oro.notification.emailnotification.additional_email_associations.label',
                'tooltip'  => 'oro.notification.emailnotification.additional_associations.tooltip',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices'  => $choices
            ]
        );
    }

    /**
     * @param string   $entityName
     * @param array    $choices
     * @param string[] $currentPath
     * @param string[] $currentLabelPath
     */
    private function collectEmailFieldsRecursive(
        string $entityName,
        array &$choices,
        array $currentPath = [],
        array $currentLabelPath = []
    ): void {
        $associations = $this->associationProvider->getAssociations($entityName);
        foreach ($associations as $associationName => $association) {
            $associationLabel = $association['label'];
            $targetClass = $association['target_class'];
            if (is_a($targetClass, EmailHolderInterface::class, true)) {
                $associationPath = $this->buildPath($currentPath, $associationName, '.');
                $associationLabelPath = $this->buildPath($currentLabelPath, $associationLabel, self::LABEL_GLUE);
                $choices[$associationLabelPath] = $associationPath;
            }

            if (count($currentPath) < self::MAX_NESTING_LEVEL - 1) {
                $this->collectEmailFieldsRecursive(
                    $targetClass,
                    $choices,
                    array_pad($currentPath, count($currentPath) + 1, $associationName),
                    array_pad($currentLabelPath, count($currentLabelPath) + 1, $associationLabel)
                );
            }
        }
    }

    private function buildPath(array $parentPath, string $association, string $glue): string
    {
        if (!$parentPath) {
            return $association;
        }

        return implode($glue, array_merge($parentPath, [$association]));
    }
}
