<?php

namespace Oro\Bundle\FormBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

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
