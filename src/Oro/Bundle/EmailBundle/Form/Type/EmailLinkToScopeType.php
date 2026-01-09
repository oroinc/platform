<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Form type for selecting email link scope in configuration.
 *
 * Provides a choice field for configuring the scope of email linking functionality,
 * determining how emails are associated with entities.
 */
class EmailLinkToScopeType extends AbstractType
{
    public const NAME = 'oro_email_link_to_scope';

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
