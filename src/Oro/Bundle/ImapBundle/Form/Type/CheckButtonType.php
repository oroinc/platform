<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines Check connection button for Email Origin
 * configuration forms
 */
class CheckButtonType extends ButtonType
{
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_imap_configuration_check';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ButtonType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['attr' => ['class' => 'btn btn-primary']]);
    }
}
