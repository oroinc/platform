<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Functional\DataFixtures;

class LoadValidEmbeddedFormAliasData extends AbstractEmbeddedFormDataFixture
{
    const FORM_REFERENCE = 'embedded_form_reference';

    /**
     * {@inheritdoc}
     */
    protected function getEmbeddedFormData(): array
    {
        return [
            [
               'reference' => self::FORM_REFERENCE,
               'formType' => 'oro_test.form.type.oro_test_workflow_aware_entity_type'
            ]
        ];
    }
}
