<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Functional\DataFixtures;

class LoadWrongServiceEmbeddedFormAliasData extends AbstractEmbeddedFormDataFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getEmbeddedFormData(): array
    {
        return [
            [
               'formType' => 'oro_test.entity_alias_resolver'
            ]
        ];
    }
}
