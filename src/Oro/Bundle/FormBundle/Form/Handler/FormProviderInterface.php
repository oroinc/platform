<?php

namespace Oro\Bundle\FormBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;

interface FormProviderInterface
{
    /**
     * Use $options['data'] to pass form data.
     * @param mixed $data
     * @param array $options
     * @return FormInterface
     */
    public function getForm($data = null, array $options = []);
}
