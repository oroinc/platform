<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\EntityExtend;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendEntityMetadataProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class ExtendEntityMetadataProviderTest extends WebTestCase
{
    protected ?ExtendEntityMetadataProvider $metadataProvider;

    public function setUp(): void
    {
        $this->bootKernel();
        $this->metadataProvider = $this->getContainer()->get('oro_entity_extend.entity_metadata_provider');
    }

    public function testGetExtendEntityMetadata(): void
    {
        $entityMetadata = $this->metadataProvider->getExtendEntityMetadata(User::class);

        self::assertSame($entityMetadata->getId()->getScope(), 'extend');
        self::assertSame($entityMetadata->getId()->getClassName(), User::class);
    }

    public function testGetExtendEntityMetadataForNotExtend(): void
    {
        $entityMetadata = $this->metadataProvider->getExtendEntityMetadata(EmailUser::class);

        self::assertSame($entityMetadata->getId()->getScope(), 'extend');
        self::assertSame($entityMetadata->getId()->getClassName(), EmailUser::class);

        self::assertFalse($entityMetadata->getValues()['is_extend']);
    }

    /**
     * @dataProvider getExtendEntityFieldsMetadataDataProvider
     */
    public function testGetExtendEntityFieldsMetadata(string $class, array $expectedResult)
    {
        $entityFieldsMetadata = $this->metadataProvider->getExtendEntityFieldsMetadata($class);

        foreach ($expectedResult as $property => $value) {
            self::assertArrayHasKey($property, $entityFieldsMetadata);
            self::assertContains($value, $expectedResult);
        }
    }

    public function getExtendEntityFieldsMetadataDataProvider(): array
    {
        return [
            'extend property' => [
                'class' => User::class,
                'expectedResult' => [
                    'phone' => [
                        'fieldName' => 'phone',
                        'fieldType' => 'string',
                        'is_extend' => true,
                        'is_serialized' => false,
                    ],
                ]
            ],
            'enum property' => [
                'class' => User::class,
                'expectedResult' => [
                    'auth_status' => [
                        'fieldName' => 'auth_status',
                        'fieldType' => 'enum',
                        'is_extend' => true,
                        'is_serialized' => false,
                    ],
                ]
            ],
            'other typed property' => [
                'class' => User::class,
                'expectedResult' => [
                    'avatar' => [
                        'fieldName' => 'avatar',
                        'fieldType' => 'image',
                        'is_extend' => true,
                        'is_serialized' => false,
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider getRealEntityFieldsMetadataDataProvider
     */
    public function testGetRealEntityFieldsMetadata(string $class, array $expectedResult)
    {
        $entityFieldsMetadata = $this->metadataProvider->getExtendEntityFieldsMetadata($class);

        foreach ($expectedResult as $property => $value) {
            self::assertArrayNotHasKey($property, $entityFieldsMetadata);
        }
    }

    public function getRealEntityFieldsMetadataDataProvider(): array
    {
        return [
            'real property' => [
                'class' => User::class,
                'expectedResult' => [
                    'email' => [
                        'fieldName' => 'email',
                        'fieldType' => 'string',
                        'is_extend' => true,
                        'is_serialized' => false,
                    ],
                ]
            ],
        ];
    }

    public function testGetExtendMetadataForNotExtend()
    {
        $entityFieldsMetadata = $this->metadataProvider->getExtendEntityFieldsMetadata(EmailUser::class);

        self::assertEmpty($entityFieldsMetadata);
    }
}
