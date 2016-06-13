<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;

use Oro\Component\Testing\Unit\EntityTrait;

class LocalizationChoicesProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var LanguageCodeFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $languageFormatter;

    /** @var FormattingCodeFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $formattingFormatter;

    /** @var LocalizationChoicesProvider */
    protected $provider;

    protected function setUp()
    {
        $this->repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->languageFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formattingFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new LocalizationChoicesProvider(
            $this->repository,
            $this->configManager,
            $this->languageFormatter,
            $this->formattingFormatter
        );
    }

    protected function tearDown()
    {
        unset(
            $this->provider,
            $this->repository,
            $this->configManager,
            $this->languageFormatter,
            $this->formattingFormatter
        );
    }

    public function testGetLanguageChoices()
    {
        $this->assertConfigManagerCalled();

        $choices = $this->provider->getLanguageChoices();

        $this->assertInternalType('array', $choices);
        $this->assertArrayHasKey('zh_Hans', $choices);
        $this->assertArrayNotHasKey('de_DE', $choices);
        $this->assertEquals('chino simplificado', $choices['zh_Hans']);
    }

    public function testGetFormattingChoices()
    {
        $this->assertConfigManagerCalled();

        $choices = $this->provider->getFormattingChoices();

        $this->assertInternalType('array', $choices);
        $this->assertArrayHasKey('br_FR', $choices);
        $this->assertArrayNotHasKey('ho', $choices);
        $this->assertEquals('bretón (Francia)', $choices['br_FR']);
    }

    public function testGetLocalizationChoices()
    {
        /** @var Localization $entity1 */
        $entity1 = $this->getEntity(Localization::class, ['id' => 100, 'name' => 'test1']);
        /** @var Localization $entity2 */
        $entity2 = $this->getEntity(Localization::class, ['id' => 42, 'name' => 'test2']);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with([], ['name' => 'ASC'])
            ->willReturn([$entity1, $entity2]);

        $this->assertEquals(
            [
                $entity1->getId() => $entity1,
                $entity2->getId() => $entity2
            ],
            $this->provider->getLocalizationChoices()
        );
    }

    protected function assertConfigManagerCalled()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_locale.language')
            ->willReturn('es');
    }
}
