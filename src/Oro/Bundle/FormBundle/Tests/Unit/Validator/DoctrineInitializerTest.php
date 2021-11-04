<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator;

use Oro\Bundle\FormBundle\Validator\DoctrineInitializer;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\Form\Util\OrderedHashMap;
use Symfony\Component\Validator\ObjectInitializerInterface;

class DoctrineInitializerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectInitializerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerInitializer;

    /** @var DoctrineInitializer */
    private $doctrineInitializer;

    protected function setUp(): void
    {
        $this->innerInitializer = $this->createMock(ObjectInitializerInterface::class);

        $this->doctrineInitializer = new DoctrineInitializer($this->innerInitializer);
    }

    /**
     * @dataProvider predefinedNotManageableObjectsProvider
     */
    public function testInitializeForPredefinedNotManageableObjects(object $object)
    {
        $this->innerInitializer->expects(self::never())
            ->method('initialize');

        $this->doctrineInitializer->initialize($object);
    }

    public function predefinedNotManageableObjectsProvider(): array
    {
        return [
            [$this->createMock(FormInterface::class)],
            [$this->createMock(OrderedHashMap::class)],
        ];
    }

    public function testInitializeForNotPredefinedObject()
    {
        $object = new \stdClass();

        $this->innerInitializer->expects(self::once())
            ->method('initialize')
            ->with(self::identicalTo($object));

        $this->doctrineInitializer->initialize($object);
    }
}
