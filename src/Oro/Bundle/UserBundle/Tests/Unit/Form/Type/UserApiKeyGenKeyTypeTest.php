<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Oro\Bundle\UserBundle\Form\Type\UserApiKeyGenKeyType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserApiKeyGenKeyTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserApiKeyGenKeyType */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->type = new UserApiKeyGenKeyType();
    }

    public function testConfigureOptions()
    {
        $options = ['disabled', 'attr'];
        $optionsResolver = new OptionsResolver();
        $this->type->configureOptions($optionsResolver);
        $this->assertEquals($options, $optionsResolver->getDefinedOptions());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(UserApiKeyGenKeyType::NAME, $this->type->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(HiddenType::class, $this->type->getParent());
    }
}
