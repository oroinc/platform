<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\EventListener;

use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\EventListener\LocaleListener;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Component\Translation\Translator;

class LocaleListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var TranslatableListener|\PHPUnit\Framework\MockObject\MockObject */
    private $transListener;

    /** @var RequestContextAwareInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var Translator|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var CurrentLocalizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $currentLocalizationProvider;

    /** @var ApplicationState|\PHPUnit\Framework\MockObject\MockObject */
    private $applicationState;

    private string $defaultLocale;

    protected function setUp(): void
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->translator =  $this->createMock(Translator::class);
        $this->transListener = $this->createMock(TranslatableListener::class);
        $this->router = $this->createMock(RequestContextAwareInterface::class);
        $this->currentLocalizationProvider = $this->createMock(CurrentLocalizationProvider::class);
        $this->applicationState = $this->createMock(ApplicationState::class);

        $this->defaultLocale = \Locale::getDefault();
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->defaultLocale);
    }

    private function getListener(): LocaleListener
    {
        return new LocaleListener(
            $this->localeSettings,
            $this->currentLocalizationProvider,
            $this->transListener,
            $this->translator,
            $this->router,
            $this->applicationState
        );
    }

    /**
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
            $this->currentLocalizationProvider->expects(self::once())
                ->method('getCurrentLocalization')
                ->willReturn($localization);
            $this->localeSettings->expects($localization ? self::never() : self::once())
                ->method('getLanguage')
                ->willReturn($customLanguage);
            $this->localeSettings->expects(self::once())
                ->method('getLocale')
                ->willReturn($customLocale);
            $this->router->expects(self::once())
                ->method('getContext')
                ->willReturn($context);
        } else {
            $this->localeSettings->expects(self::never())
                ->method('getLanguage');
            $this->localeSettings->expects(self::never())
                ->method('getLocale');
        }

        if ($installed) {
            $this->applicationState->expects(self::once())
                ->method('isInstalled')
                ->willReturn(true);
        } else {
            $this->applicationState->expects(self::once())
                ->method('isInstalled')
                ->willReturn(false);
        }

        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $this->getListener()->onKernelRequest($event);

        if ($isSetLocale) {
            self::assertEquals($expectedLanguage, $request->getLocale());
            self::assertEquals($expectedLanguage, $context->getParameter('_locale'));
            self::assertEquals($customLocale, \Locale::getDefault());
        } else {
            self::assertEquals($this->defaultLocale, $request->getLocale());
            self::assertEquals($this->defaultLocale, \Locale::getDefault());
        }
    }

    public function onKernelRequestDataProvider(): array
    {
        return [
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
            'application installed with localization' => [
                'installed' => true,
                'isSetLocale' => true,
                'language' => 'en_US',
                'localization' => (new Localization())->setLanguage((new Language())->setCode('en_US')),
            ],
        ];
    }

    public function testOnConsoleCommand(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::once())
            ->method('hasParameterOption')
            ->willReturn(false);

        $event = new ConsoleCommandEvent(null, $input, $this->createMock(OutputInterface::class));

        $this->localeSettings->expects(self::once())
            ->method('getLocale');

        $this->localeSettings->expects(self::once())
            ->method('getLanguage');

        $this->transListener->expects(self::once())
            ->method('setTranslatableLocale');

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $this->getListener()->onConsoleCommand($event);
    }
}
