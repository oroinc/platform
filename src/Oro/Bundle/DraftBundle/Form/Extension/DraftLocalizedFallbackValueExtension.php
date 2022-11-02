<?php

namespace Oro\Bundle\DraftBundle\Form\Extension;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Responsible for clearing parameters from LocalizedFallbackValue fields, as to save draft.
 * LocalizedFallbackValue are unique and cannot be duplicated.
 * (LocalizedFallbackValue 'ids' are used to update the fields, but in the draft, 'ids' should always new)
 */
class DraftLocalizedFallbackValueExtension extends AbstractTypeExtension
{
    /**
     * @var DraftHelper
     */
    private $draftHelper;

    public function __construct(DraftHelper $draftHelper)
    {
        $this->draftHelper = $draftHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    public function preSubmit(FormEvent $event): void
    {
        if ($this->draftHelper->isSaveAsDraftAction()) {
            if ($this->checkRootEntityIsDraftable($event->getForm())) {
                $data = $event->getData();
                unset($data[LocalizedFallbackValueCollectionType::FIELD_IDS]);
                $event->setData($data);
            }
        }
    }

    private function checkRootEntityIsDraftable(FormInterface $form): bool
    {
        if ($form->getParent()) {
            return $this->checkRootEntityIsDraftable($form->getParent());
        }

        return $form->getData() instanceof DraftableInterface;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [LocalizedFallbackValueCollectionType::class];
    }
}
