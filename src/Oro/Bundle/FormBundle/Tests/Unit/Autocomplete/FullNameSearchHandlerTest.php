<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\FormBundle\Autocomplete\FullNameSearchHandler;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class FullNameSearchHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var FullNameSearchHandler */
    private $searchHandler;

    protected function setUp(): void
    {
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);

        $this->searchHandler = new FullNameSearchHandler('FooEntityClass', ['name', 'email']);
        $this->searchHandler->setPropertyAccessor(new PropertyAccessor());
    }

    public function testConvertItem()
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

    public function testConvertItemFails()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Name resolver must be configured');

        $this->searchHandler->convertItem(new \stdClass());
    }
}
