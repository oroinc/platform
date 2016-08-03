<?php

namespace Oro\Bundle\NoteBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Form\EventListener\NoteSubscriber;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;

class NoteApiType extends NoteType
{
    /** @var  ConfigManager $configManager */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'message',
                'text',
                [
                    'required' => true,
                    'label' => 'oro.note.message.label'
                ]
            );

        $builder->addEventSubscriber(new PatchSubscriber());
        $builder->addEventSubscriber(new NoteSubscriber($this->configManager));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => Note::ENTITY_NAME,
                'intention'          => 'note',
                'csrf_protection'    => false
            ]
        );
    }

    /**
     * {@inheritdoc}
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
        return 'oro_note_api';
    }
}
