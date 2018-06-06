<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\EmbeddedFormBundle\Tests\Functional\Stubs\EmbeddedFormStub;

class LoadEmbeddedFormData extends AbstractEmbeddedFormDataFixture
{
    const EMBEDDED_FORM = 'embedded_form';

    /**
     * {@inheritdoc}
     */
    protected function getEmbeddedFormData(): array
    {
        return [
            [
                'reference' => self::EMBEDDED_FORM,
                'formType' => EmbeddedFormStub::class
            ]
        ];
    }
}
