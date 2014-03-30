<?php

namespace Oro\Bundle\FormBundle\Utils;

use Symfony\Component\Form\FormInterface;

class FormUtils
{
    /**
     * Replace form field by the same field with different options
     * Example of usage:
     *    - need to disable some field
     *      FormUtils::replaceField($form, 'fieldName', ['disabled' => true])
     *
     * @param FormInterface $form
     * @param string        $fieldName
     * @param array         $modifyOptions
     * @param array         $unsetOptions ['optionName' ...]
     */
    public static function replaceField(
        FormInterface $form,
        $fieldName,
        array $modifyOptions = [],
        array $unsetOptions = []
    ) {
        $field = $form->get($fieldName);
        $config = $field->getConfig()->getOptions();

        if (array_key_exists('auto_initialize', $config)) {
            $config['auto_initialize'] = false;
        }
        $config = array_merge($config, $modifyOptions);
        $config = array_diff_key($config, array_flip($unsetOptions));
        $form->add($fieldName, $field->getConfig()->getType()->getName(), $config);
    }
}
