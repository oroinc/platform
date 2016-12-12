<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Helper;

use Symfony\Component\DomCrawler\Form;

trait FallbackTestTrait
{
    /**
     * @param Form $form
     * @param mixed $scalarValue
     * @param mixed $fallbackValue
     * @param string $formName
     * @param string $fieldName
     * @return Form
     */
    protected function updateFallbackField(
        Form $form,
        $scalarValue,
        $fallbackValue,
        $formName,
        $fieldName
    ) {
        $scalarFieldName = sprintf('%s[%s][scalarValue]', $formName, $fieldName);
        if (is_null($scalarValue)) {
            unset($form[$scalarFieldName]);
        } else {
            $form[$scalarFieldName] = $scalarValue;
        }
        $fallbackFieldName = sprintf('%s[%s][fallback]', $formName, $fieldName);
        if ($fallbackValue) {
            $form[$fallbackFieldName] = $fallbackValue;
        } else {
            unset($form[$fallbackFieldName]);
        }

        return $form;
    }
}
