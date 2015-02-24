<?php

/** @var Oro\Component\Layout\LayoutManipulatorInterface $layoutManipulator */
/** @var Oro\Component\Layout\LayoutItemInterface $item */

$layoutManipulator->setOption('form_css', 'content', $item->getContext()->get('embedded_form_entity')->getCss());
