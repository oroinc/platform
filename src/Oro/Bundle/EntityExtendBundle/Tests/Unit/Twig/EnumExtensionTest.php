<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\EntityExtendBundle\Twig\EnumExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnumExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private EnumOptionsProvider&MockObject $enumOptionsProvider;
    private EnumExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->enumOptionsProvider = $this->createMock(EnumOptionsProvider::class);

        $container = self::getContainerBuilder()
            ->add(EnumOptionsProvider::class, $this->enumOptionsProvider)
            ->getContainer($this);

        $this->extension = new EnumExtension($container);
    }

    public function testTransEnum(): void
    {
        $values = [
            'Value 1' => 'test_enum_code.val1'
        ];

        $this->enumOptionsProvider->expects($this->any())
            ->method('getEnumChoicesByCode')
            ->with('test_enum_code')
            ->willReturn($values);

        $this->assertEquals(
            'Value 1',
            self::callTwigFilter($this->extension, 'trans_enum', ['test_enum_code.val1'])
        );
        $this->assertEquals(
            null,
            self::callTwigFilter($this->extension, 'trans_enum', ['test_enum_code.val2'])
        );
    }

    public function testTransEnumWhenLabelIsZero(): void
    {

        $values = [
            '0' => 'test_enum_code.val1'
        ];

        $this->enumOptionsProvider->expects($this->any())
            ->method('getEnumChoicesByCode')
            ->with('test_enum_code')
            ->willReturn($values);

        $this->assertEquals(
            '0',
            self::callTwigFilter($this->extension, 'trans_enum', ['test_enum_code.val1'])
        );
    }

    public function testTransEnumWhenIdIsZero(): void
    {
        $values = [
           'Value 1' => 'test_enum_code.0',
        ];

        $this->enumOptionsProvider->expects($this->any())
            ->method('getEnumChoicesByCode')
            ->with('test_enum_code')
            ->willReturn($values);

        $this->assertEquals(
            'Value 1',
            self::callTwigFilter($this->extension, 'trans_enum', ['test_enum_code.0'])
        );
    }

    public function testTransEnumWhenIdsAreNumeric(): void
    {
        $values = [
            'Value 1' => 'test_enum_code.05',
            'Value 2' => 'test_enum_code.5'
        ];

        $this->enumOptionsProvider->expects($this->any())
            ->method('getEnumChoicesByCode')
            ->with('test_enum_code')
            ->willReturn($values);

        $this->assertEquals(
            'Value 2',
            self::callTwigFilter($this->extension, 'trans_enum', ['test_enum_code.5'])
        );
    }

    public function testSortEnum(): void
    {
        $values = [
            'Value 3' => 'test_enum_code.val3',
            'Value 1' => 'test_enum_code.val1',
            'Value 4' => 'test_enum_code.val4',
            'Value 2' => 'test_enum_code.val2',
        ];

        $this->enumOptionsProvider->expects($this->any())
            ->method('getEnumChoicesByCode')
            ->with('test_enum_code')
            ->willReturn($values);

        $this->assertEquals(
            ['test_enum_code.val1', 'test_enum_code.val4', 'test_enum_code.val2'],
            self::callTwigFilter(
                $this->extension,
                'sort_enum',
                [
                    [
                        'test_enum_code.val2',
                        'test_enum_code.val4',
                        'test_enum_code.val1'
                    ]
                ]
            )
        );

        // call one ore time to check local cache
        $this->assertEquals(
            ['test_enum_code.val3', 'test_enum_code.val1', 'test_enum_code.val4', 'test_enum_code.val2'],
            self::callTwigFilter(
                $this->extension,
                'sort_enum',
                [
                    [
                        'test_enum_code.val1',
                        'test_enum_code.val2',
                        'test_enum_code.val3',
                        'test_enum_code.val4'
                    ]
                ]
            )
        );
        // call when the list of ids is a string
        $this->assertEquals(
            ['test_enum_code.val1', 'test_enum_code.val4', 'test_enum_code.val2'],
            self::callTwigFilter(
                $this->extension,
                'sort_enum',
                [
                    json_encode(['test_enum_code.val1', 'test_enum_code.val4', 'test_enum_code.val2'])
                ]
            )
        );
    }
}
