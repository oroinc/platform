<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileMimeTypeConfigType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class MimeTypeConfigTypeTest extends FormIntegrationTestCase
{
    private const ALLOWED_MIME_TYPES = [
        'application/vnd.ms-excel',
        'application/pdf',
        'image/png'
    ];

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [new FileMimeTypeConfigType(self::ALLOWED_MIME_TYPES)],
                []
            )
        ];
    }

    /**
     * @dataProvider validValuesDataProvider
     */
    public function testWithValidValue($value)
    {
        $form = $this->factory->create(FileMimeTypeConfigType::class);
        $form->submit($value);
        self::assertTrue($form->isSynchronized());
        self::assertSame(implode("\n", $value), $form->getData());
    }

    public function validValuesDataProvider()
    {
        return [
            'empty'              => [
                []
            ],
            'part of MIME types' => [
                ['application/vnd.ms-excel', 'image/png']
            ],
            'all MIME types'     => [
                self::ALLOWED_MIME_TYPES
            ]
        ];
    }

    /**
     * @dataProvider invalidValuesDataProvider
     */
    public function testWithInvalidValue($value)
    {
        $form = $this->factory->create(FileMimeTypeConfigType::class);
        $form->submit($value);
        self::assertFalse($form->isSynchronized());
    }

    public function invalidValuesDataProvider()
    {
        return [
            'invalid one MIME type'     => [
                ['application/pdf', 'application/not_allowed']
            ],
            'invalid several MIME type' => [
                ['application/pdf', 'application/not_allowed1', 'application/not_allowed2']
            ]
        ];
    }

    /**
     * @dataProvider validModelDataProvider
     */
    public function testWithValidValueWithNotEmptyModelData($data)
    {
        $form = $this->factory->create(FileMimeTypeConfigType::class, $data);
        self::assertEquals(['application/pdf', 'image/png'], $form->getNormData());

        $value = ['image/png', 'application/vnd.ms-excel'];
        $form->submit($value);
        self::assertTrue($form->isSynchronized());
        self::assertSame(implode("\n", $value), $form->getData());
    }

    public function validModelDataProvider()
    {
        return [
            'LF delimiter'                     => [
                "application/pdf\nimage/png"
            ],
            'CRLF delimiter'                   => [
                "application/pdf\r\nimage/png"
            ],
            'Comma delimiter'                  => [
                'application/pdf,image/png'
            ],
            'Comma delimiter with whitespaces' => [
                'application/pdf , image/png'
            ]
        ];
    }
}
