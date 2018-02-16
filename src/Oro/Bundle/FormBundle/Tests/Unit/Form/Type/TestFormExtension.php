<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractExtension;
use Oro\Bundle\FormBundle\Form\Type\Select2Type;

class TestFormExtension extends AbstractExtension
{
    protected function loadTypes()
    {
        return array(
            new Select2Type('Symfony\Component\Form\Extension\Core\Type\HiddenType', 'oro_select2_hidden')
        );
    }
}
