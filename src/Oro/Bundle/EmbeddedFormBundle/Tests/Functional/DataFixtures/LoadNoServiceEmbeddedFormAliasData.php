<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Functional\DataFixtures;

class LoadNoServiceEmbeddedFormAliasData extends AbstractEmbeddedFormDataFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getEmbeddedFormData(): array
    {
        return [
            [
               'formType' => 'oro_test.form.no_service'
            ]
        ];
    }
}
