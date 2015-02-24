<?php

/** @var Oro\Component\Layout\LayoutManipulatorInterface $layoutManipulator */
/** @var Oro\Component\Layout\LayoutItemInterface $item */

$formEntity = $item->getContext()->get('embedded_form_entity');

$layoutManipulator->add(
    'success_message',
    'content',
    new Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormSuccessType(),
    [
        'message' => $formEntity->getSuccessMessage(),
        'form_id' => $formEntity->getId()
    ]
);
