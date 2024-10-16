<?php

namespace Oro\Bundle\FormBundle\Provider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class CallbackFormTemplateDataProvider implements FormTemplateDataProviderInterface
{
    /**
     * @var callable
     */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    #[\Override]
    public function getData($entity, FormInterface $form, Request $request)
    {
        return call_user_func($this->callable, $entity, $form, $request);
    }
}
