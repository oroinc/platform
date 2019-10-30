<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateApiType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailTemplateApiTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var EmailTemplateApiType */
    private $type;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        $this->type = new EmailTemplateApiType($this->configManager, $this->localizationManager);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->configureOptions($resolver);
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->any())->method('add')->willReturnSelf();
        $builder->expects($this->any())->method('get')->willReturnSelf();

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber'));

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['oro_form.wysiwyg_enabled'], ['oro_email.sanitize_html'])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->localizationManager->expects($this->once())
            ->method('getLocalizations')
            ->willReturn([
                1 => new Localization(),
                42 => new Localization(),
            ]);

        $this->type->buildForm($builder, []);
    }
}
