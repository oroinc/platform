<?php

/** @var Oro\Component\Layout\LayoutManipulatorInterface $layoutManipulator */
/** @var Oro\Component\Layout\LayoutItemInterface $item */

$layoutManipulator->add(
    'form',
    'content',
    new Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormType(),
    [
        'form'        => $item->getContext()->get('embedded_form')->createView(),
        // @deprecated since 1.7. Kept for backward compatibility
        'form_layout' => $item->getContext()->get('embedded_form_custom_layout')
    ]
);
