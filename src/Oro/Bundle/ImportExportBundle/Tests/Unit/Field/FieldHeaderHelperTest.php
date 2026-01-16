<?php

declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Field;

use Oro\Bundle\EntityBundle\Helper\FieldHelper as EntityFieldHelper;
use Oro\Bundle\ImportExportBundle\Field\FieldHeaderHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldHeaderHelperTest extends TestCase
{
    private EntityFieldHelper|MockObject $entityFieldHelper;
    private FieldHeaderHelper $fieldHeaderHelper;

    protected function setUp(): void
    {
        $this->entityFieldHelper = $this->createMock(EntityFieldHelper::class);
        $this->fieldHeaderHelper = new FieldHeaderHelper($this->entityFieldHelper);
    }

    public function testBuildRelationFieldHeader(): void
    {
        $this->entityFieldHelper->expects($this->exactly(2))
            ->method('getEntityFields')
            ->willReturnCallback(function (string $entityClass, int $options) {
                if ($entityClass === 'App\Entity\Product') {
                    return [
                        ['name' => 'sku', 'label' => 'SKU'],
                        ['name' => 'category', 'label' => 'Category'],
                    ];
                }
                if ($entityClass === 'App\Entity\Category') {
                    return [
                        ['name' => 'id', 'label' => 'ID'],
                        ['name' => 'title', 'label' => 'Title'],
                    ];
                }
                return [];
            });

        $header = $this->fieldHeaderHelper->buildRelationFieldHeader(
            'App\Entity\Product',
            'category',
            'App\Entity\Category',
            'id'
        );

        $this->assertEquals('Category.ID', $header);
    }

    public function testBuildRelationFieldHeaderWithCustomDelimiter(): void
    {
        $this->entityFieldHelper->expects($this->exactly(2))
            ->method('getEntityFields')
            ->willReturnCallback(function (string $entityClass) {
                if ($entityClass === 'App\Entity\Order') {
                    return [
                        ['name' => 'customer', 'label' => 'Customer'],
                    ];
                }
                if ($entityClass === 'App\Entity\Customer') {
                    return [
                        ['name' => 'name', 'label' => 'Name'],
                    ];
                }
                return [];
            });

        $header = $this->fieldHeaderHelper->buildRelationFieldHeader(
            'App\Entity\Order',
            'customer',
            'App\Entity\Customer',
            'name',
            ':'
        );

        $this->assertEquals('Customer:Name', $header);
    }

    public function testBuildRelationFieldHeaderWithMissingLabels(): void
    {
        $this->entityFieldHelper->expects($this->exactly(2))
            ->method('getEntityFields')
            ->willReturnCallback(function () {
                return []; // No fields found
            });

        $header = $this->fieldHeaderHelper->buildRelationFieldHeader(
            'App\Entity\Product',
            'category',
            'App\Entity\Category',
            'id'
        );

        // Should fall back to ucfirst of field names
        $this->assertEquals('Category.Id', $header);
    }
}
