<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Form\Extension\LocalizationScopeExtension;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationSelectType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Extension\Stub\LocalizationSelectTypeStub;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeType;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

class LocalizationScopeExtensionTest extends FormIntegrationTestCase
{
    /**
     * @var LocalizationScopeExtension
     */
    protected $localizationScopeExtension;

    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject $scopeManager
     */
    protected $scopeManager;

    protected function setUp()
    {
        $this->localizationScopeExtension = new LocalizationScopeExtension();

        parent::setUp();
    }

    public function testBuildForm()
    {
        $this->scopeManager->expects($this->once())
            ->method('getScopeEntities')
            ->with('web_content')
            ->willReturn(['localization' => Localization::class]);

        $form = $this->factory->create(
            ScopeType::NAME,
            null,
            [ScopeType::SCOPE_TYPE_OPTION => 'web_content']
        );

        $this->assertTrue($form->has('localization'));
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(ScopeType::class, $this->localizationScopeExtension->getExtendedType());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [
            new PreloadedExtension(
                [
                    ScopeType::NAME => new ScopeType($this->scopeManager),
                    LocalizationSelectType::NAME => new LocalizationSelectTypeStub(),
                ],
                [
                    ScopeType::NAME => [$this->localizationScopeExtension],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
