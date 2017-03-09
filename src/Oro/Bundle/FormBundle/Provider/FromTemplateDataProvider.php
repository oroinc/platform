<?php

namespace Oro\Bundle\FormBundle\Provider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class FromTemplateDataProvider implements FormTemplateDataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getData($entity, FormInterface $form, Request $request)
    {
        return [
            'form' => $form->createView()
        ];
    }
}
