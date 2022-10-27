<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractMultiChoiceType extends AbstractChoiceType
{
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct($translator);
    }
}
