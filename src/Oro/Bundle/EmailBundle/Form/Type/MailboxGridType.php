<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Form type for mailbox selection in grid contexts.
 *
 * Provides a hidden form field for storing mailbox identifiers in datagrids,
 * used for mailbox-specific filtering and configuration.
 */
class MailboxGridType extends AbstractType
{
    const NAME = 'oro_email_mailbox_grid';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return HiddenType::class;
    }
}
