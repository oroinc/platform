<?php

namespace Oro\Bundle\FormBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface FormHandlerInterface
{
    /**
     * @param mixed $data
     * @param FormInterface $form
     * @param Request $request
     *
     * @return bool
     */
    public function process($data, FormInterface $form, Request $request);
}
