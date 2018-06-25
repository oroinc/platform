<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\EventListener;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\EventListener\LocaleListener;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RequestContext;

class LocaleListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LocaleListener */
    protected $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $localeSettings;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $transListener;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var CurrentLocalizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $currentLocalizationProvider;

    /** @var string */
    protected $defaultLocale;

    protected function setUp()
    {
        $this->localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator =  $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $this->transListener = $this->createMock('Gedmo\Translatable\TranslatableListener');
        $this->router = $this->createMock('Symfony\Component\Routing\RequestContextAwareInterface');
        $this->currentLocalizationProvider = $this->createMock(CurrentLocalizationProvider::class);

        $this->defaultLocale = \Locale::getDefault();

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturnCallback(
                function ($serviceName) {
                    if ($serviceName === 'oro_locale.settings') {
                        return $this->localeSettings;
                    }
                    if ($serviceName === 'stof_doctrine_extensions.listener.translatable') {
                        return $this->transListener;
                    }
                    if ($serviceName === 'router') {
                        return $this->router;
                    }
                    if ($serviceName === 'translator') {
                        return $this->translator;
                    }
                    if ($serviceName === 'oro_locale.provider.current_localization') {
                        return $this->currentLocalizationProvider;
                    }
                }
            );
    }

    protected function tearDown()
    {
        \Locale::setDefault($this->defaultLocale);
    }

    /**
     * @param bool|null $installed
     * @param bool $isSetLocale
     * @param string $expectedLanguage
     * @param Localization|null $localization
     * @dataProvider onKernelRequestDataProvider
     */
    public function testOnKernelRequest($installed, $isSetLocale, $expectedLanguage, Localization $localization = null)
    {
        $customLanguage = 'ru';
        $customLocale = 'fr';

        $request = new Request();
        $context = new RequestContext();
        $request->setDefaultLocale($this->defaultLocale);

        if ($isSetLocale) {
            $this->currentLocalizationProvider->expects($this->once())->method('getCurrentLocalization')
                ->willReturn($localization);
            $this->localeSettings->expects($localization ? $this->never() : $this->once())->method('getLanguage')
                ->will($this->returnValue($customLanguage));
            $this->localeSettings->expects($this->once())->method('getLocale')
                ->will($this->returnValue($customLocale));
            $this->router->expects($this->once())->method('getContext')->willReturn($context);
        } else {
            $this->localeSettings->expects($this->never())->method('getLanguage');
            $this->localeSettings->expects($this->never())->method('getLocale');
        }

        $this->container->expects($this->any())
            ->method('getParameter')
            ->with('installed')
            ->willReturn($installed);
        $this->listener = new LocaleListener($this->container);
        $this->listener->onKernelRequest($this->createGetResponseEvent($request));

        if ($isSetLocale) {
            $this->assertEquals($expectedLanguage, $request->getLocale());
            $this->assertEquals($expectedLanguage, $context->getParameter('_locale'));
            $this->assertEquals($customLocale, \Locale::getDefault());
        } else {
            $this->assertEquals($this->defaultLocale, $request->getLocale());
            $this->assertEquals($this->defaultLocale, \Locale::getDefault());
        }
    }

    /**
     * @return array
     */
    public function onKernelRequestDataProvider()
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

    public function testOnConsoleCommand()
    {
        $this->container->expects($this->any())
            ->method('getParameter')
            ->with('installed')
            ->willReturn(true);
        $this->listener = new LocaleListener($this->container);

        $event = $this
            ->getMockBuilder('Symfony\Component\Console\Event\ConsoleCommandEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $input = $this
            ->getMockBuilder('Symfony\Component\Console\Input\InputInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $event
            ->expects($this->once())
            ->method('getInput')
            ->will($this->returnValue($input));

        $input
            ->expects($this->once())
            ->method('hasParameterOption')
            ->will($this->returnValue(false));

        $this->localeSettings
            ->expects($this->once())
            ->method('getLocale');

        $this->localeSettings
            ->expects($this->once())
            ->method('getLanguage');

        $this->transListener
            ->expects($this->once())
            ->method('setTranslatableLocale');

        $this->listener->onConsoleCommand($event);
    }

    /**
     * @param Request $request
     * @return GetResponseEvent
     */
    protected function createGetResponseEvent(Request $request)
    {
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->setMethods(['getRequest'])
            ->getMock();

        $event->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        return $event;
    }
}
