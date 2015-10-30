<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SecurityBundle\Form\Type\ShareScopeType;
use Oro\Bundle\SecurityBundle\Form\Model\Share;

class ShareScopeTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var ShareScopeType */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->type = new ShareScopeType($configManager);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_share_scope', $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->type->getParent());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'multiple' => true,
                    'expanded' => true,
                    'choices' => [
                        Share::SHARE_SCOPE_USER => 'oro.security.share_scopes.user.label',
                        Share::SHARE_SCOPE_BUSINESS_UNIT => 'oro.security.share_scopes.business_unit.label',
                    ]
                ]
            );
        $resolver->expects($this->once())
            ->method('setNormalizers');
        $this->type->setDefaultOptions($resolver);
    }
}
