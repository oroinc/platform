<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Builder for creating {@see Update} instances with fluent interface.
 *
 * This class provides a convenient way to construct {@see Update} objects by accepting
 * all required dependencies (form data, form, handler, and template data provider)
 * and configuring them on a new Update instance using a fluent API.
 */
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
