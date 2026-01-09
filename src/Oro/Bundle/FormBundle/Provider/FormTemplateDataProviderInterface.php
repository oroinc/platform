<?php

namespace Oro\Bundle\FormBundle\Provider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the contract for providing template data for form rendering.
 *
 * Implementations of this interface are responsible for preparing data that should
 * be passed to templates when rendering forms, allowing customization of the
 * template context based on the entity, form, and request.
 */
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
