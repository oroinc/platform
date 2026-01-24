<?php

namespace Oro\Bundle\FormBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Delegates form processing to a custom callback function.
 *
 * This handler allows flexible form processing by accepting a callable that
 * implements the form processing logic. The callback receives the form data,
 * form instance, and request, enabling custom handling without creating a
 * dedicated handler class.
 */
class CallbackFormHandler implements FormHandlerInterface
{
    /**
     * @var callable
     */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    #[\Override]
    public function process($data, FormInterface $form, Request $request)
    {
        return call_user_func($this->callback, $data, $form, $request);
    }
}
