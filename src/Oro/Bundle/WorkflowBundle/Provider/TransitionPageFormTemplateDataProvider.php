<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides template data for workflow transition form rendering.
 *
 * This provider prepares the entity and form view data needed to render transition
 * forms in page templates.
 */
class TransitionPageFormTemplateDataProvider implements FormTemplateDataProviderInterface
{
    #[\Override]
    public function getData($entity, FormInterface $form, Request $request)
    {
        return [
            'entity' => $entity,
            'form' => $form->createView()
        ];
    }
}
