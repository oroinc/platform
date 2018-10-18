<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class EmailLinkToScopeType extends AbstractType
{
    const NAME = 'oro_email_link_to_scope';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
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
}
