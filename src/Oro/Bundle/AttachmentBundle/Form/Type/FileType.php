<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType as SymfonyFileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class FileType extends AbstractType
{
    const NAME = 'oro_file';

    /**
     * @var EventSubscriberInterface
     */
    protected $eventSubscriber;

    /**
     * @param EventSubscriberInterface $eventSubscriber
     */
    public function setEventSubscriber(EventSubscriberInterface $eventSubscriber)
    {
        $this->eventSubscriber = $eventSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $constraints = [];
        if ($options['checkEmptyFile']) {
            $constraints = [
                new NotBlank()
            ];
        }

        $builder->add(
            'file',
            SymfonyFileType::class,
            [
                'label'       => 'oro.attachment.file.label',
                'required'    => $options['checkEmptyFile'],
                'constraints' => $constraints
            ]
        );

        if ($options['addEventSubscriber']) {
            $builder->addEventSubscriber($this->eventSubscriber);
        }
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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'     => 'Oro\Bundle\AttachmentBundle\Entity\File',
                'checkEmptyFile' => false,
                'allowDelete' => true,
                'addEventSubscriber' => true
            ]
        );
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['allow_delete'] = $options['allowDelete'];
    }
}
