<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;

/**
 * Class WidgetUserMultiselect
 * @package Oro\Bundle\DashboardBundle\Form\Type
 */
class WidgetUserMultiselect extends UserMultiSelectType
{
    const NAME = 'oro_type_widget_user_multiselect';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
