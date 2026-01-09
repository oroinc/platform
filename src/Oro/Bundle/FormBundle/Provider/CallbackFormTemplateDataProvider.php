<?php

namespace Oro\Bundle\FormBundle\Provider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Delegates template data provision to a custom callback function.
 *
 * This provider allows flexible template data generation by accepting a callable
 * that implements the data provision logic. The callback receives the entity,
 * form, and request, enabling custom data preparation without creating a
 * dedicated provider class.
 */
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
