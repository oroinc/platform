<?php

namespace Oro\Bundle\FormBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This trait assists with form submitting when request method might not match form's method. (Please note that submit
 * method does not accept Request as parameter since Symfony 3.0).
 * It forms submit data based on request and handles cases when form does not have name and is compound, also it
 * includes files in submit data if any are present.
 */
trait RequestHandlerTrait
{
    /**
     * Submits data from post or put Request to a given form.
     *
     * @param FormInterface $form
     * @param Request $request
     * @param bool $clearMissing
     */
    private function submitPostPutRequest(FormInterface $form, Request $request, bool $clearMissing = true)
    {
        $requestData = $form->getName()
            ? $request->request->get($form->getName(), [])
            : $request->request->all();

        $filesData = $form->getName()
            ? $request->files->get($form->getName(), [])
            : $request->files->all();

        $form->submit(array_replace_recursive($requestData, $filesData), $clearMissing);
    }
}
