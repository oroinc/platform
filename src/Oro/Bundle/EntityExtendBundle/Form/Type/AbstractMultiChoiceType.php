<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractChoiceType;

abstract class AbstractMultiChoiceType extends AbstractChoiceType
{
    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct($translator);
    }
}
