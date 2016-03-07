<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\AbstractType;

class ActionCombinedButtonsType extends AbstractType
{
    const NAME = 'action_combined_buttons';

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
        return ActionLineButtonsType::NAME;
    }
}
