<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Twig\EnumExtension;

class EnumExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var EnumExtension */
    protected $extension;

    protected function setUp()
    {
        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new EnumExtension($this->doctrine);
    }

    public function testTransEnumLocalCache()
    {
        $enumCode1             = 'test_enum1';
        $enumCode2             = 'test_enum2';
        $enumValueEntityClass1 = ExtendHelper::buildEnumValueClassName($enumCode1);
        $enumValueEntityClass2 = ExtendHelper::buildEnumValueClassName($enumCode2);

        $repo1 = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo2 = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->exactly(2))
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        [$enumValueEntityClass1, null, $repo1],
                        [$enumValueEntityClass2, null, $repo2],
                    ]
                )
            );
        $repo1->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue([]));
        $repo2->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue([]));

        $this->extension->transEnum('val1', $enumValueEntityClass1);
        $this->extension->transEnum('val1', $enumValueEntityClass2);
        // call one more time to check local cache
        $this->extension->transEnum('val1', $enumValueEntityClass1);
        $this->extension->transEnum('val1', $enumValueEntityClass2);
        // call with enum code to check local cache keys
        $this->extension->transEnum('val1', $enumCode1);
        $this->extension->transEnum('val1', $enumCode2);
    }

    public function testTransEnum()
    {
        $enumValueEntityClass = 'Test\EnumValue';

        $values = [
            new TestEnumValue('val1', 'Value 1')
        ];

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with($enumValueEntityClass)
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($values));

        $this->assertEquals(
            'Value 1',
            $this->extension->transEnum('val1', $enumValueEntityClass)
        );
        $this->assertEquals(
            'val2',
            $this->extension->transEnum('val2', $enumValueEntityClass)
        );
    }

    public function testTransEnumWhenLabelIsZero()
    {
        $enumValueEntityClass = 'Test\EnumValue';

        $values = [
            new TestEnumValue('val1', '0')
        ];

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with($enumValueEntityClass)
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($values));

        $this->assertEquals(
            '0',
            $this->extension->transEnum('val1', $enumValueEntityClass)
        );
    }

    public function testTransEnumWhenIdIsZero()
    {
        $enumValueEntityClass = 'Test\EnumValue';

        $values = [
            new TestEnumValue('0', 'Value 1')
        ];

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with($enumValueEntityClass)
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($values));

        $this->assertEquals(
            'Value 1',
            $this->extension->transEnum('0', $enumValueEntityClass)
        );
    }

    public function testSortEnum()
    {
        $enumValueEntityClass = 'Test\EnumValue';

        $values = [
            new TestEnumValue('val1', 'Value 1', 2),
            new TestEnumValue('val2', 'Value 2', 4),
            new TestEnumValue('val3', 'Value 3', 1),
            new TestEnumValue('val4', 'Value 4', 3),
        ];

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with($enumValueEntityClass)
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($values));

        $this->assertEquals(
            ['val1', 'val4', 'val2'],
            $this->extension->sortEnum(['val2', 'val4', 'val1'], $enumValueEntityClass)
        );
        // call one ore time to check local cache
        $this->assertEquals(
            ['val3', 'val1', 'val4', 'val2'],
            $this->extension->sortEnum(['val1', 'val2', 'val3', 'val4'], $enumValueEntityClass)
        );
        // call when the list of ids is a string
        $this->assertEquals(
            ['val1', 'val4', 'val2'],
            $this->extension->sortEnum('val1,val2,val4', $enumValueEntityClass)
        );
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(2, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('sort_enum', $filters[0]->getName());
        $callable = $filters[0]->getCallable();
        $this->assertCount(2, $callable);
        $this->assertSame($this->extension, $callable[0]);
        $this->assertEquals('sortEnum', $callable[1]);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[1]);
        $this->assertEquals('trans_enum', $filters[1]->getName());
        $callable = $filters[1]->getCallable();
        $this->assertCount(2, $callable);
        $this->assertSame($this->extension, $callable[0]);
        $this->assertEquals('transEnum', $callable[1]);
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_enum',
            $this->extension->getName()
        );
    }
}
