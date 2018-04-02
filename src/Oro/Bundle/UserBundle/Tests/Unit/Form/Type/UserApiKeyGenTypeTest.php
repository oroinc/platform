<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Form\Type\UserApiKeyGenKeyType;
use Oro\Bundle\UserBundle\Form\Type\UserApiKeyGenType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class UserApiKeyGenTypeTest extends FormIntegrationTestCase
{
    public function testSubmit()
    {
        $userApi = new UserApi();
        $form = $this->factory->create(UserApiKeyGenType::class, $userApi);
        $form->submit([]);
        $this->assertTrue($form->isValid());

        $this->assertEquals($userApi, $form->getData());
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testApiKeyElementIdIsRequiredOption()
    {
        $this->factory->create(UserApiKeyGenType::class, null, ['apiKeyElementId' => null]);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testApiKeyElementIdIsStringOption()
    {
        $this->factory->create(UserApiKeyGenType::class, null, ['apiKeyElementId' => new \stdClass]);
    }

    public function testDefaultOptions()
    {
        $expected   = [
            'data_class' => UserApi::class,
            'csrf_protection' => ['enabled' => true, 'fieild_name' => 'apikey_token'],
            'csrf_token_id' => UserApiKeyGenType::NAME,
            'apiKeyElementId' => 'user-apikey-gen-elem'
        ];
        $form       = $this->factory->create(UserApiKeyGenType::class, null, []);
        $defaults   = array_intersect_key($expected, $form->getConfig()->getOptions());

        $this->assertEquals($expected, $defaults);
    }

    public function testGetName()
    {
        $type = new UserApiKeyGenType();
        $this->assertEquals(UserApiKeyGenType::NAME, $type->getName());
    }

    public function testGetBlockPrefix()
    {
        $type = new UserApiKeyGenType();
        $this->assertEquals(UserApiKeyGenType::NAME, $type->getBlockPrefix());
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    UserApiKeyGenType::class => new UserApiKeyGenType(),
                    UserApiKeyGenKeyType::class => new UserApiKeyGenKeyType()
                ],
                []
            )
        ];
    }
}
