<?php

namespace Oro\Bundle\ApiBundle\Processor\Create;

use Oro\Bundle\ApiBundle\Form\EventListener\CreateListener;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildFormBuilder as BaseBuildFormBuilder;

/**
 * Builds the form builder based on the entity metadata and configuration
 * and sets it to the Context.
 */
class BuildFormBuilder extends BaseBuildFormBuilder
{
    /**
     * {@inheritdoc}
     */
    protected function getFormBuilder(FormContext $context)
    {
        $formBuilder = parent::getFormBuilder($context);
        $formBuilder->addEventSubscriber(new CreateListener());

        return $formBuilder;
    }
}
