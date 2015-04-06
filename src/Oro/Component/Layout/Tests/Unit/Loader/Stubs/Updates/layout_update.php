<?php

/** @var Oro\Component\Layout\LayoutManipulatorInterface $layoutManipulator */
/** @var Oro\Component\Layout\LayoutItemInterface $item */

$layoutManipulator
    ->add('header', 'root_alias', 'header')
    ->add('logo', 'header', 'logo', ['title' => 'test'])
    ->addAlias('root_alias', 'root');
