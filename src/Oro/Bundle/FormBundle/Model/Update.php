<?php

namespace Oro\Bundle\FormBundle\Model;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;

class Update
{
    /** @var FormInterface */
    public $form;

    /** @var object */
    public $data;

    /** @var string */
    public $saveMessage;

    /** @var FormTemplateDataProviderInterface */
    public $resultDataProvider;

    /** @var FormHandlerInterface */
    public $handler;
}
