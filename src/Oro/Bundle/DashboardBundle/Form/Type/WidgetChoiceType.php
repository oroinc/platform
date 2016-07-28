<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class WidgetChoiceType extends AbstractType
{
    const NAME = 'oro_type_widget_choice';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
