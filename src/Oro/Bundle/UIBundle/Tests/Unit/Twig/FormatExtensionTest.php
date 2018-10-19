<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Formatter\FormatterManager;
use Oro\Bundle\UIBundle\Twig\FormatExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\Translation\TranslatorInterface;

class FormatExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $formatterManager;

    /** @var FormatExtension */
    protected $extension;

    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formatterManager = $this->getMockBuilder(FormatterManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('translator', $this->translator)
            ->add('oro_ui.formatter', $this->formatterManager)
            ->getContainer($this);

        $this->extension = new FormatExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_formatter_extension', $this->extension->getName());
    }

    public function testFormat()
    {
        $parameter = 'test';
        $formatterName = 'test_format';
        $formatterArguments = [];
        $expected = 'result';

        $this->formatterManager->expects($this->once())
            ->method('format')
            ->with($parameter, $formatterName, $formatterArguments)
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFilter(
                $this->extension,
                'oro_format',
                [$parameter, $formatterName, $formatterArguments]
            )
        );
    }

    /**
     * @dataProvider formatFilenameProvider
     */
    public function testFormatFilename($filename, $result)
    {
        $this->assertEquals(
            $result,
            self::callTwigFunction($this->extension, 'oro_format_filename', [$filename])
        );
    }

    public function formatFilenameProvider()
    {
        return [
            [
                'filename' => '',
                'result' => '',
            ],
            [
                'filename' => 'somename.jpg',
                'result' => 'somename.jpg',
            ],
            [
                'filename' => 'somename_very_long_file_name.jpg',
                'result' => 'somenam..ame.jpg',
            ],
            [
                'filename' => 'somename123.jpg',
                'result' => 'somename123.jpg',
            ],
            [
                'filename' => 'somename1234.jpg',
                'result' => 'somenam..234.jpg',
            ],
            [
                'filename' => 'тратата.jpg',
                'result' => 'тратата.jpg',
            ],
            [
                'filename' => 'тратататратататратата.jpg',
                'result' => 'тратата..ата.jpg',
            ],
        ];
    }

    /**
     * @dataProvider ageDataProvider
     */
    public function testGetAge($date, $options, $age)
    {
        $this->assertEquals(
            $age,
            self::callTwigFilter($this->extension, 'age', [$date, $options])
        );
    }

    public function testGetAgeAsStringInvertDiff()
    {
        $date = new \DateTime('+1 year', new \DateTimeZone('UTC'));

        $this->assertEquals(
            '',
            self::callTwigFilter($this->extension, 'age_string', [$date, []])
        );
        $this->assertEquals(
            'N/A',
            self::callTwigFilter($this->extension, 'age_string', [$date, ['default' => 'N/A']])
        );
    }

    public function testGetAgeAsString()
    {
        $date = new \DateTime('-1 year -1 month', new \DateTimeZone('UTC'));

        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with('oro.age', 1, ['%count%' => 1])
            ->will($this->returnValue('age 1'));

        $this->assertEquals(
            'age 1',
            self::callTwigFilter($this->extension, 'age_string', [$date, []])
        );
    }

    public function ageDataProvider()
    {
        $oneYearAgo = new \DateTime('-1 year', new \DateTimeZone('UTC'));
        $oneMonthAgo = new \DateTime('-1 month', new \DateTimeZone('UTC'));
        $oneYearTwoMonthAgo = new \DateTime('-1 year -2 months', new \DateTimeZone('UTC'));
        $tenYearsAgo = new \DateTime('-10 years', new \DateTimeZone('UTC'));
        $inFuture = new \DateTime('+1 year', new \DateTimeZone('UTC'));

        return [
            [$oneYearAgo->format('Y-m-d'), [], 1],
            [$oneYearAgo->format('m/d/Y'), ['format' => 'm/d/Y'], 1],
            [$tenYearsAgo->format('m/d/Y'), ['format' => 'm/d/Y', 'timezone' => 'UTC'], 10],
            [$oneMonthAgo, [], 0],
            [$oneYearAgo, [], 1],
            [$oneMonthAgo, [], 0],
            [$oneYearTwoMonthAgo, [], 1],
            [$tenYearsAgo, [], 10],
            [$inFuture, [], null],
            [$inFuture, ['default' => 'N/A'], null],
            [null, [], null],
            ['', [], null],
        ];
    }
}
