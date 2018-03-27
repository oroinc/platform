<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Twig\EnumExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class EnumExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var EnumExtension */
    protected $extension;

    /** @var EnumValueProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $enumValueProvider;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->enumValueProvider = $this->createMock(EnumValueProvider::class);

        $this->extension = new EnumExtension($this->doctrine);
    }

    public function testTransEnumLocalCache()
    {
        $enumCode1             = 'test_enum1';
        $enumCode2             = 'test_enum2';
        $enumValueEntityClass1 = ExtendHelper::buildEnumValueClassName($enumCode1);
        $enumValueEntityClass2 = ExtendHelper::buildEnumValueClassName($enumCode2);

        $repo1 = $this->createMock(EntityRepository::class);
        $repo2 = $this->createMock(EntityRepository::class);
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

        self::callTwigFilter($this->extension, 'trans_enum', ['val1', $enumValueEntityClass1]);
        self::callTwigFilter($this->extension, 'trans_enum', ['val1', $enumValueEntityClass2]);
        // call one more time to check local cache
        self::callTwigFilter($this->extension, 'trans_enum', ['val1', $enumValueEntityClass1]);
        self::callTwigFilter($this->extension, 'trans_enum', ['val1', $enumValueEntityClass2]);
        // call with enum code to check local cache keys
        self::callTwigFilter($this->extension, 'trans_enum', ['val1', $enumCode1]);
        self::callTwigFilter($this->extension, 'trans_enum', ['val1', $enumCode2]);
    }

    public function testTransEnum()
    {
        $enumValueEntityClass = 'Test\EnumValue';

        $values = [
            new TestEnumValue('val1', 'Value 1')
        ];

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with($enumValueEntityClass)
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($values));

        $this->enumValueProvider->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            'Value 1',
            self::callTwigFilter($this->extension, 'trans_enum', ['val1', $enumValueEntityClass])
        );
        $this->assertEquals(
            'val2',
            self::callTwigFilter($this->extension, 'trans_enum', ['val2', $enumValueEntityClass])
        );
    }

    public function testTransEnumFromProvider()
    {
        $enumValueEntityClass = 'Test\EnumValue';

        $this->doctrine->expects($this->never())
            ->method('getRepository');

        $this->enumValueProvider->expects($this->once())
            ->method('getEnumChoices')
            ->with($enumValueEntityClass)
            ->willReturn(['val1' => 'Value 1']);
        $this->extension->setEnumValueProvider($this->enumValueProvider);

        $this->assertEquals(
            'Value 1',
            self::callTwigFilter($this->extension, 'trans_enum', ['val1', $enumValueEntityClass])
        );
    }

    public function testTransEnumWhenLabelIsZero()
    {
        $enumValueEntityClass = 'Test\EnumValue';

        $values = [
            new TestEnumValue('val1', '0')
        ];

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with($enumValueEntityClass)
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($values));

        $this->assertEquals(
            '0',
            self::callTwigFilter($this->extension, 'trans_enum', ['val1', $enumValueEntityClass])
        );
    }

    public function testTransEnumWhenIdIsZero()
    {
        $enumValueEntityClass = 'Test\EnumValue';

        $values = [
            new TestEnumValue('0', 'Value 1')
        ];

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with($enumValueEntityClass)
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($values));

        $this->assertEquals(
            'Value 1',
            self::callTwigFilter($this->extension, 'trans_enum', ['0', $enumValueEntityClass])
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

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with($enumValueEntityClass)
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($values));

        $this->assertEquals(
            ['val1', 'val4', 'val2'],
            self::callTwigFilter($this->extension, 'sort_enum', [['val2', 'val4', 'val1'], $enumValueEntityClass])
        );
        // call one ore time to check local cache
        $this->assertEquals(
            ['val3', 'val1', 'val4', 'val2'],
            self::callTwigFilter(
                $this->extension,
                'sort_enum',
                [['val1', 'val2', 'val3', 'val4'], $enumValueEntityClass]
            )
        );
        // call when the list of ids is a string
        $this->assertEquals(
            ['val1', 'val4', 'val2'],
            self::callTwigFilter($this->extension, 'sort_enum', ['val1,val2,val4', $enumValueEntityClass])
        );
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_enum',
            $this->extension->getName()
        );
    }
}
