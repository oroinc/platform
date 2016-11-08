<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeType;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScopeTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeManager;

    /**
     * @var ScopeType
     */
    protected $scopeType;

    protected function setUp()
    {
        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeType = new ScopeType($this->scopeManager);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver **/
        $resolver = $this->getMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setRequired')
            ->with([
                ScopeType::SCOPE_TYPE_OPTION,
            ]);

        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with(ScopeType::SCOPE_TYPE_OPTION, ['string']);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                ScopeType::SCOPE_FIELDS_OPTION => []
            ]);

        $resolver->expects($this->once())
            ->method('setNormalizer')
            ->with(
                ScopeType::SCOPE_FIELDS_OPTION,
                function (Options $options) {
                    return $this->scopeManager->getScopeEntities($options[ScopeType::SCOPE_TYPE_OPTION]);
                }
            );

        $this->scopeType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(ScopeType::NAME, $this->scopeType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ScopeType::NAME, $this->scopeType->getBlockPrefix());
    }
}
