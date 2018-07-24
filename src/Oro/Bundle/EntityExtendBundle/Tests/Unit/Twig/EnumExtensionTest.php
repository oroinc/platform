<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Twig\EnumExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class EnumExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var EnumValueProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $enumValueProvider;

    /** @var EnumExtension */
    protected $extension;

    protected function setUp()
    {
        $this->enumValueProvider = $this->createMock(EnumValueProvider::class);

        $this->extension = new EnumExtension($this->enumValueProvider);
    }

    public function testTransEnum()
    {
        $enumValueEntityClass = 'Test\EnumValue';

        $values = [
            'Value 1' => 'val1'
        ];

        $this->enumValueProvider->expects($this->any())
            ->method('getEnumChoices')
            ->with($enumValueEntityClass)
            ->willReturn($values);

        $this->assertEquals(
            'Value 1',
            self::callTwigFilter($this->extension, 'trans_enum', ['val1', $enumValueEntityClass])
        );
        $this->assertEquals(
            'val2',
            self::callTwigFilter($this->extension, 'trans_enum', ['val2', $enumValueEntityClass])
        );
    }

    public function testTransEnumWhenLabelIsZero()
    {
        $enumValueEntityClass = 'Test\EnumValue';

        $values = [
            '0' => 'val1'
        ];

        $this->enumValueProvider->expects($this->any())
            ->method('getEnumChoices')
            ->with($enumValueEntityClass)
            ->willReturn($values);

        $this->assertEquals(
            '0',
            self::callTwigFilter($this->extension, 'trans_enum', ['val1', $enumValueEntityClass])
        );
    }

    public function testTransEnumWhenIdIsZero()
    {
        $enumValueEntityClass = 'Test\EnumValue';

        $values = [
           'Value 1' => '0',
        ];

        $this->enumValueProvider->expects($this->any())
            ->method('getEnumChoices')
            ->with($enumValueEntityClass)
            ->willReturn($values);

        $this->assertEquals(
            'Value 1',
            self::callTwigFilter($this->extension, 'trans_enum', ['0', $enumValueEntityClass])
        );
    }

    public function testSortEnum()
    {
        $enumValueEntityClass = 'Test\EnumValue';

        $values = [
            'Value 3' => 'val3',
            'Value 1' => 'val1',
            'Value 4' => 'val4',
            'Value 2' => 'val2',
        ];

        $this->enumValueProvider->expects($this->any())
            ->method('getEnumChoices')
            ->with($enumValueEntityClass)
            ->willReturn($values);

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
