<?php

use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;

/** @var LayoutManipulatorInterface $layoutManipulator */
/** @var LayoutItemInterface $item */

$layoutManipulator
    ->add('header', 'root_alias', 'header')
    ->add('logo', 'header', 'logo', ['title' => 'test'])
    ->addAlias('root_alias', 'root');
