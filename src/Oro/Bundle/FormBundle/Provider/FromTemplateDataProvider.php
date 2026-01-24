<?php

namespace Oro\Bundle\FormBundle\Provider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides template data containing the form view.
 *
 * This provider returns a simple data array containing the form view, which is
 * the most common template data requirement for form rendering. It serves as a
 * basic implementation suitable for standard form display scenarios.
 */
class FromTemplateDataProvider implements FormTemplateDataProviderInterface
{
    #[\Override]
    public function getData($entity, FormInterface $form, Request $request)
    {
        return [
            'form' => $form->createView()
        ];
    }
}
