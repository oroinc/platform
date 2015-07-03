<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EmailBundle\Provider\MailboxProcessorProvider;

class ManualMailboxProcessorType extends AbstractType
{
    /** @var MailboxProcessorProvider */
    private $processorProvider;

    public function __construct(MailboxProcessorProvider $processorProvider)
    {
        $this->processorProvider = $processorProvider;
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'oro_email_mailbox_processor_manual';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\EmailBundle\Entity\ManualMailboxProcessor'
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('role', 'text', [
            'label' => 'Test',
            'mapped' => false,
            'required' => false,
        ]);
    }
}
