<?php

namespace Oro\Bundle\FormBundle\Provider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface FormTemplateDataProviderInterface
{
    /**
     * @param object $entity
     * @param FormInterface $form
     * @param Request $request
     * @return array
     */
    public function getData($entity, FormInterface $form, Request $request);
}
