<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Helper;

use Symfony\Component\DomCrawler\Form;

trait FallbackTestTrait
{
    private function updateFallbackField(
        Form $form,
        mixed $scalarValue,
        mixed $fallbackValue,
        string $formName,
        string $fieldName
    ): Form {
        $scalarFieldName = sprintf('%s[%s][scalarValue]', $formName, $fieldName);
        if (null === $scalarValue) {
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
