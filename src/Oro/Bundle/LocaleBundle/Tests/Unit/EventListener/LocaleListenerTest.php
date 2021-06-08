<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\EventListener;

use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\EventListener\LocaleListener;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Component\Translation\Translator;

class LocaleListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private LocaleSettings|\PHPUnit\Framework\MockObject\MockObject $localeSettings;

    private TranslatableListener|\PHPUnit\Framework\MockObject\MockObject $transListener;

    private RequestContextAwareInterface|\PHPUnit\Framework\MockObject\MockObject $router;

    private Translator|\PHPUnit\Framework\MockObject\MockObject $translator;

    private CurrentLocalizationProvider|\PHPUnit\Framework\MockObject\MockObject $currentLocalizationProvider;

    private string $defaultLocale;

    protected function setUp(): void
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->translator =  $this->createMock(Translator::class);
        $this->transListener = $this->createMock(TranslatableListener::class);
        $this->router = $this->createMock(RequestContextAwareInterface::class);
        $this->currentLocalizationProvider = $this->createMock(CurrentLocalizationProvider::class);

        $this->defaultLocale = \Locale::getDefault();
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->defaultLocale);
    }

    /**
     * @param bool|string|null $installed
     * @param bool $isSetLocale
     * @param string $expectedLanguage
     * @param Localization|null $localization
     *
     * @dataProvider onKernelRequestDataProvider
     */
    public function testOnKernelRequest(
        bool|string|null $installed,
        bool $isSetLocale,
        string $expectedLanguage,
        Localization $localization = null
    ): void {
        $customLanguage = 'ru';
        $customLocale = 'fr';

        $request = new Request();
        $context = new RequestContext();
        $request->setDefaultLocale($this->defaultLocale);

        if ($isSetLocale) {
            $this->currentLocalizationProvider->expects(self::once())->method('getCurrentLocalization')
                ->willReturn($localization);
            $this->localeSettings->expects($localization ? self::never() : self::once())->method('getLanguage')
                ->willReturn($customLanguage);
            $this->localeSettings->expects(self::once())->method('getLocale')
                ->willReturn($customLocale);
            $this->router->expects(self::once())->method('getContext')->willReturn($context);
        } else {
            $this->localeSettings->expects(self::never())->method('getLanguage');
            $this->localeSettings->expects(self::never())->method('getLocale');
        }

        $listener = new LocaleListener(
            $this->localeSettings,
            $this->currentLocalizationProvider,
            $this->transListener,
            $this->translator,
            $this->router,
            $installed
        );

        $listener->onKernelRequest($this->createRequestEvent($request));

        if ($isSetLocale) {
            self::assertEquals($expectedLanguage, $request->getLocale());
            self::assertEquals($expectedLanguage, $context->getParameter('_locale'));
            self::assertEquals($customLocale, \Locale::getDefault());
        } else {
            self::assertEquals($this->defaultLocale, $request->getLocale());
            self::assertEquals($this->defaultLocale, \Locale::getDefault());
        }
    }

    /**
     * @return array
     */
    public function onKernelRequestDataProvider(): array
    {
        return [
            'application not installed with null' => [
                'installed' => null,
                'isSetLocale' => false,
                'language' => 'ru',
                'localization' => null,
            ],
            'application not installed with false' => [
                'installed' => false,
                'isSetLocale' => false,
                'language' => 'ru',
                'localization' => null,
            ],
            'application installed with flag' => [
                'installed' => true,
                'isSetLocale' => true,
                'language' => 'ru',
                'localization' => null,
            ],
            'application installed with date' => [
                'installed' => '2012-12-12T12:12:12+02:00',
                'isSetLocale' => true,
                'language' => 'ru',
                'localization' => null,
            ],
            'application installed and localization' => [
                'installed' => '2012-12-12T12:12:12+02:00',
                'isSetLocale' => true,
                'language' => 'en_US',
                'localization' => (new Localization())->setLanguage(
                    $this->getEntity(Language::class, ['code' => 'en_US',])
                ),
            ],
        ];
    }

    public function testOnConsoleCommand(): void
    {
        $event = $this
            ->getMockBuilder('Symfony\Component\Console\Event\ConsoleCommandEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $input = $this
            ->getMockBuilder('Symfony\Component\Console\Input\InputInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $event
            ->expects(self::once())
            ->method('getInput')
            ->willReturn($input);

        $input
            ->expects(self::once())
            ->method('hasParameterOption')
            ->willReturn(false);

        $this->localeSettings
            ->expects(self::once())
            ->method('getLocale');

        $this->localeSettings
            ->expects(self::once())
            ->method('getLanguage');

        $this->transListener
            ->expects(self::once())
            ->method('setTranslatableLocale');

        $listener = new LocaleListener(
            $this->localeSettings,
            $this->currentLocalizationProvider,
            $this->transListener,
            $this->translator,
            $this->router,
            true
        );
        $listener->onConsoleCommand($event);
    }

    /**
     * @param Request $request
     * @return RequestEvent
     */
    private function createRequestEvent(Request $request): RequestEvent
    {
        $event = $this->createMock(RequestEvent::class);

        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        return $event;
    }
}
