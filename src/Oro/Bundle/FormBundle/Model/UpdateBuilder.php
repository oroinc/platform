<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\Form\FormInterface;

class UpdateBuilder
{
    /**
     * @param object $data
     * @param FormInterface $form
     * @param FormHandlerInterface $handler
     * @param FormTemplateDataProviderInterface $dataProvider
     * @return Update
     */
    public function build(
        $data,
        FormInterface $form,
        FormHandlerInterface $handler,
        FormTemplateDataProviderInterface $dataProvider
    ) {
        $update = new Update();
        $update->setFormData($data)
            ->setFrom($form)
            ->setHandler($handler)
            ->setTemplateDataProvider($dataProvider);

        return $update;
    }
}
