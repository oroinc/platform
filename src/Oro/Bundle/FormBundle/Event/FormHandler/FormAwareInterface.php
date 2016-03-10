<?php

namespace Oro\Bundle\FormBundle\Event\FormHandler;

use Symfony\Component\Form\FormInterface;

interface FormAwareInterface
{
    /**
     * @return FormInterface
     */
    public function getForm();
}
