<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Provider\MultipleFileConstraintsProvider;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Form type for File collection
 */
class MultiFileType extends AbstractType
{
    const TYPE = 'oro_attachment_multi_file';

    /** @var EventSubscriberInterface */
    private $eventSubscriber;

    /** @var MultipleFileConstraintsProvider */
    private $constraintsProvider;

    public function __construct(
        EventSubscriberInterface $eventSubscriber,
        MultipleFileConstraintsProvider $constraintsProvider
    ) {
        $this->eventSubscriber = $eventSubscriber;
        $this->constraintsProvider = $constraintsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->eventSubscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $rootClass = $form->getRoot()->getConfig()->getDataClass();
        $fieldName = $form->getConfig()->getName();
        $maxNumber = $this->constraintsProvider->getMaxNumberOfFilesForEntityField($rootClass, $fieldName);

        $view->vars['maxNumber'] = $maxNumber;

        usort($view->children, function (FormView $leftFileItemView, FormView $rightFileItemView) {
            /** @var FileItem $leftFileItem */
            $leftFileItem = $leftFileItemView->vars['data'];
            /** @var FileItem $rightFileItem */
            $rightFileItem = $rightFileItemView->vars['data'];

            return $leftFileItem->getSortOrder() - $rightFileItem->getSortOrder();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type' => FileItemType::class,
            'error_bubbling' => false,
            'constraints' => [
                new Valid(),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }
}
