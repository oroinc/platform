<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Formatter\FormatterManager;
use Oro\Bundle\UIBundle\Provider\UrlWithoutFrontControllerProvider;
use Oro\Bundle\UIBundle\Twig\FormatExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormatExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private UrlWithoutFrontControllerProvider&MockObject $urlProvider;
    private FormatterManager&MockObject $formatterManager;
    private TranslatorInterface&MockObject $translator;
    private FormatExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->urlProvider = $this->createMock(UrlWithoutFrontControllerProvider::class);
        $this->formatterManager = $this->createMock(FormatterManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_ui.provider.url_without_front_controller', $this->urlProvider)
            ->add(FormatterManager::class, $this->formatterManager)
            ->add(TranslatorInterface::class, $this->translator)
            ->getContainer($this);

        $this->extension = new FormatExtension($container);
    }

    public function testFormat(): void
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

    public function testGenerateUrlWithoutFrontController(): void
    {
        $name = 'some_route_name';
        $parameters = ['any_route_parameter'];
        $path = 'some/test/path.png';

        $this->urlProvider->expects($this->once())
            ->method('generate')
            ->with($name, $parameters)
            ->willReturn($path);

        self::assertEquals($path, self::callTwigFunction($this->extension, 'asset_path', [$name, $parameters]));
    }

    /**
     * @dataProvider formatFilenameProvider
     */
    public function testFormatFilename(string $filename, string $result): void
    {
        $this->assertEquals(
            $result,
            self::callTwigFunction($this->extension, 'oro_format_filename', [$filename])
        );
    }

    public function formatFilenameProvider(): array
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
    public function testGetAge(\DateTime|string|null $date, array $options, ?int $age): void
    {
        $this->assertEquals(
            $age,
            self::callTwigFilter($this->extension, 'age', [$date, $options])
        );
    }

    public function testGetAgeAsStringInvertDiff(): void
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

    public function testGetAgeAsString(): void
    {
        $date = new \DateTime('-1 year -1 month', new \DateTimeZone('UTC'));

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.age', ['%count%' => 1])
            ->willReturn('age 1');

        $this->assertEquals(
            'age 1',
            self::callTwigFilter($this->extension, 'age_string', [$date, []])
        );
    }

    public function ageDataProvider(): array
    {
        $isFeb29 = date('md') === '0229';
        $oneYearAgo = new \DateTime('-1 year' . ($isFeb29 ? ' -1 day' : ''), new \DateTimeZone('UTC'));
        $oneMonthAgo = new \DateTime('-1 month', new \DateTimeZone('UTC'));
        $oneYearTwoMonthAgo = new \DateTime('-1 year -2 months', new \DateTimeZone('UTC'));
        $tenYearsAgo = new \DateTime('-10 years' . ($isFeb29 ? ' -1 day' : ''), new \DateTimeZone('UTC'));
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
