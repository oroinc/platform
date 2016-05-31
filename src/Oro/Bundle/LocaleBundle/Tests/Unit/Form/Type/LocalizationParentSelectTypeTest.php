<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Entity\Localization;

use OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type\Stub\EntityType;
use Symfony\Component\Form\AbstractType;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationParentSelectType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizationParentSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var LocalizationParentSelectType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    public function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new LocalizationParentSelectType($this->doctrineHelper);

    }

    public function tearDown()
    {
        unset($this->doctrineHelper, $this->formType);

        parent::tearDown();
    }

    public function testGetParent()
    {
        $this->assertEquals('entity', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizationParentSelectType::NAME, $this->formType->getName());
    }

    public function testSetEntityClass()
    {
        $className = 'stdClass';

        $this->assertAttributeEmpty('entityClass', $this->formType);

        $this->formType->setEntityClass($className);

        $this->assertAttributeEquals($className, 'entityClass', $this->formType);
    }

    public function testConfigureOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $optionsResolver */
        $optionsResolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()->getMock();
        $optionsResolver->expects($this->atLeastOnce())->method('setNormalizer');
        $optionsResolver->expects($this->atLeastOnce())->method('setDefaults')->willReturn($optionsResolver);
        
        $this->formType->configureOptions($optionsResolver);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'entity' => new EntityType([]),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
