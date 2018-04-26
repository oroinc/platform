<?php

namespace Oro\Bundle\FormBundle\Form\Extension\Traits;

use Symfony\Component\Form\Extension\Core\Type\FormType;

trait FormExtendedTypeTrait
{
    /**
     * Provide backward compatibility between Symfony versions < 2.8 and 2.8+
     *
     * @return string
     */
    public function getExtendedType()
    {
        return FormType::class;
    }
}
