<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

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
