<?php
namespace Oro\Bundle\FormBundle\Model;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface UpdateInterface
{
    /**
     * @param Request $request
     * @return bool
     */
    public function handle(Request $request);

    /**
     * @param Request $request
     * @return array
     */
    public function getTemplateData(Request $request);

    /**
     * @return FormInterface
     */
    public function getForm();

    /**
     * @return object
     */
    public function getFormData();
}
