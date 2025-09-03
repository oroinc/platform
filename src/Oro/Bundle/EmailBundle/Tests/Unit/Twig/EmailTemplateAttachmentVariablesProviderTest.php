<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateAttachmentVariablesProvider;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariablesProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class EmailTemplateAttachmentVariablesProviderTest extends TestCase
{
    private VariablesProvider&MockObject $variablesProvider;
    private EmailTemplateAttachmentVariablesProvider $provider;

    protected function setUp(): void
    {
        $this->variablesProvider = $this->createMock(VariablesProvider::class);
        $this->provider = new EmailTemplateAttachmentVariablesProvider($this->variablesProvider);
    }

    public function testGetAttachmentVariablesReturnsEmptyArrayWhenNoEntityVariableDefinitions(): void
    {
        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn([]);

        $result = $this->provider->getAttachmentVariables('App\\Entity\\Order');

        self::assertSame([], $result);
    }

    public function testGetAttachmentVariablesReturnsEmptyArrayWhenEntityClassNotFound(): void
    {
        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn([
                'App\\Entity\\User' => [
                    'orderIdentifier' => [
                        'label' => 'Name',
                        'type' => 'string',
                    ],
                ],
            ]);

        $result = $this->provider->getAttachmentVariables('App\\Entity\\Order');

        self::assertSame([], $result);
    }

    public function testGetAttachmentVariablesReturnsEmptyArrayWhenNoFileVariables(): void
    {
        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn([
                'App\\Entity\\Order' => [
                    'orderIdentifier' => [
                        'label' => 'Order Number',
                        'type' => 'string',
                    ],
                ],
            ]);

        $result = $this->provider->getAttachmentVariables('App\\Entity\\Order');

        self::assertSame([], $result);
    }

    public function testGetAttachmentVariablesReturnsOnlyFileVariables(): void
    {
        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn([
                'App\\Entity\\Order' => [
                    'orderIdentifier' => [
                        'label' => 'Order Number',
                        'type' => 'string',
                    ],
                    'pdfFile' => [
                        'label' => 'PDF File',
                        'type' => 'ref-one',
                        'related_entity_name' => File::class,
                    ],
                    'attachment' => [
                        'label' => 'Attachment',
                        'type' => 'ref-one',
                        'related_entity_name' => File::class,
                    ],
                    'customer' => [
                        'label' => 'Customer',
                        'type' => 'ref-one',
                        'related_entity_name' => 'App\\Entity\\Customer',
                    ],
                ],
            ]);

        $result = $this->provider->getAttachmentVariables('App\\Entity\\Order');

        $expected = [
            'entity.pdfFile' => [
                'label' => 'PDF File',
                'type' => 'ref-one',
                'related_entity_name' => File::class,
            ],
            'entity.attachment' => [
                'label' => 'Attachment',
                'type' => 'ref-one',
                'related_entity_name' => File::class,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public function testGetAttachmentVariablesWithMultipleEntityClasses(): void
    {
        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn([
                'App\\Entity\\Order' => [
                    'pdfFile' => [
                        'label' => 'PDF File',
                        'type' => 'ref-one',
                        'related_entity_name' => File::class,
                    ],
                ],
                'App\\Entity\\AnotherEntity' => [
                    'receipt' => [
                        'label' => 'Receipt',
                        'type' => 'ref-one',
                        'related_entity_name' => File::class,
                    ],
                ],
            ]);

        // Test that it only returns variables for the requested entity class
        $result = $this->provider->getAttachmentVariables('App\\Entity\\Order');

        $expected = [
            'entity.pdfFile' => [
                'label' => 'PDF File',
                'type' => 'ref-one',
                'related_entity_name' => File::class,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public function testGetAttachmentVariablesWithEmptyEntityVariables(): void
    {
        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn([
                'App\\Entity\\Order' => [],
            ]);

        $result = $this->provider->getAttachmentVariables('App\\Entity\\Order');

        self::assertSame([], $result);
    }

    public function testGetAttachmentVariablesWithFileAndFileItem(): void
    {
        $entityClass = 'App\\Entity\\Order';

        $entityVariableDefinitions = [
            $entityClass => [
                'pdfFile' => [
                    'label' => 'PDF File',
                    'type' => 'ref-one',
                    'related_entity_name' => File::class,
                ],
                'title' => [
                    'label' => 'Title',
                    'type' => 'string',
                    'related_entity_name' => null,
                ],
                'documents' => [
                    'label' => 'Documents',
                    'type' => 'multiFile',
                    'related_entity_name' => FileItem::class,
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'text',
                    // No related_entity_name key
                ],
            ],
        ];

        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn($entityVariableDefinitions);

        $result = $this->provider->getAttachmentVariables($entityClass);

        $expected = [
            'entity.pdfFile' => [
                'label' => 'PDF File',
                'type' => 'ref-one',
                'related_entity_name' => File::class,
            ],
            'entity.documents' => [
                'label' => 'Documents',
                'type' => 'multiFile',
                'related_entity_name' => FileItem::class,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public function testGetAttachmentVariablesWithMaxDepthLimitation(): void
    {
        $entityClass = 'App\\Entity\\Order';
        $userClass = 'App\\Entity\\User';

        $entityVariableDefinitions = [
            $entityClass => [
                'mainFile' => [
                    'label' => 'Main File',
                    'type' => 'ref-one',
                    'related_entity_name' => File::class,
                ],
                'owner' => [
                    'label' => 'Owner',
                    'type' => 'ref-one',
                    'related_entity_name' => $userClass,
                ],
            ],
            $userClass => [
                'avatar' => [
                    'label' => 'Avatar',
                    'type' => 'ref-one',
                    'related_entity_name' => File::class,
                ],
            ],
        ];

        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn($entityVariableDefinitions);

        // Set max depth to 1 (default)
        $this->provider->setMaxDepth(1);

        $result = $this->provider->getAttachmentVariables($entityClass);

        // Should not include entity.owner.avatar because it exceeds max depth
        $expected = [
            'entity.mainFile' => [
                'label' => 'Main File',
                'type' => 'ref-one',
                'related_entity_name' => File::class,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public function testGetAttachmentVariablesWithIncreasedMaxDepth(): void
    {
        $entityClass = 'App\\Entity\\Order';
        $userClass = 'App\\Entity\\User';

        $entityVariableDefinitions = [
            $entityClass => [
                'mainFile' => [
                    'label' => 'Main File',
                    'type' => 'ref-one',
                    'related_entity_name' => File::class,
                ],
                'owner' => [
                    'label' => 'Owner',
                    'type' => 'ref-one',
                    'related_entity_name' => $userClass,
                ],
            ],
            $userClass => [
                'avatar' => [
                    'label' => 'Avatar',
                    'type' => 'ref-one',
                    'related_entity_name' => File::class,
                ],
            ],
        ];

        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn($entityVariableDefinitions);

        // Set max depth to 2
        $this->provider->setMaxDepth(2);

        $result = $this->provider->getAttachmentVariables($entityClass);

        // Should include entity.owner.avatar because max depth is 2
        $expected = [
            'entity.mainFile' => [
                'label' => 'Main File',
                'type' => 'ref-one',
                'related_entity_name' => File::class,
            ],
            'entity.owner.avatar' => [
                'label' => 'Owner / Avatar',
                'type' => 'ref-one',
                'related_entity_name' => File::class,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public function testGetAttachmentVariablesWithEmptyEntityDefinitions(): void
    {
        $entityClass = 'App\\Entity\\Order';

        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn([]);

        $result = $this->provider->getAttachmentVariables($entityClass);

        self::assertEquals([], $result);
    }

    public function testGetAttachmentVariablesWithNonExistentEntityClass(): void
    {
        $entityClass = 'App\\Entity\\NonExistent';

        $entityVariableDefinitions = [
            'App\\Entity\\Order' => [
                'file' => [
                    'label' => 'File',
                    'type' => 'ref-one',
                    'related_entity_name' => File::class,
                ],
            ],
        ];

        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn($entityVariableDefinitions);

        $result = $this->provider->getAttachmentVariables($entityClass);

        self::assertEquals([], $result);
    }

    public function testGetAttachmentVariablesWithNoAttachmentVariables(): void
    {
        $entityClass = 'App\\Entity\\Order';

        $entityVariableDefinitions = [
            $entityClass => [
                'title' => [
                    'label' => 'Title',
                    'type' => 'string',
                    'related_entity_name' => null,
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'text',
                    'related_entity_name' => 'App\\Entity\\Category',
                ],
            ],
        ];

        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn($entityVariableDefinitions);

        $result = $this->provider->getAttachmentVariables($entityClass);

        self::assertEquals([], $result);
    }
}
