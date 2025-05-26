<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\FormBundle\Autocomplete\FullNameSearchHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FullNameSearchHandlerTest extends TestCase
{
    private EntityNameResolver&MockObject $entityNameResolver;
    private FullNameSearchHandler $searchHandler;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);

        $this->searchHandler = new FullNameSearchHandler('FooEntityClass', ['name', 'email']);
        $this->searchHandler->setPropertyAccessor(PropertyAccess::createPropertyAccessor());
    }

    public function testConvertItem(): void
    {
        $fullName = 'Mr. John Doe';

        $entity = new \stdClass();
        $entity->name = 'John';
        $entity->email = 'john@example.com';

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($entity)
            ->willReturn($fullName);

        $this->searchHandler->setEntityNameResolver($this->entityNameResolver);
        $this->assertEquals(
            [
                'name' => 'John',
                'email' => 'john@example.com',
                'fullName' => $fullName,
            ],
            $this->searchHandler->convertItem($entity)
        );
    }

    public function testConvertItemFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Name resolver must be configured');

        $this->searchHandler->convertItem(new \stdClass());
    }
}
