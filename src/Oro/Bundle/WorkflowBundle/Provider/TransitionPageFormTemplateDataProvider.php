<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;

class TransitionPageFormTemplateDataProvider implements FormTemplateDataProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getData($entity, FormInterface $form, Request $request)
    {
        return [
            'entity' => $entity,
            'form' => $form->createView()
        ];
    }
}
