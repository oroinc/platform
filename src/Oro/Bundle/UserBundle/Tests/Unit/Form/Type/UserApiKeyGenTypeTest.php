<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Form\Type\UserApiKeyGenKeyType;
use Oro\Bundle\UserBundle\Form\Type\UserApiKeyGenType;

class UserApiKeyGenTypeTest extends FormIntegrationTestCase
{
    /** @var UserApiKeyGenType */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->type = new UserApiKeyGenType();
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->type);
        parent::tearDown();
    }

    public function testSubmit()
    {
        $userApi = new UserApi();
        $form = $this->factory->create($this->type, $userApi);
        $form->submit([]);
        $this->assertTrue($form->isValid());

        $this->assertEquals($userApi, $form->getData());
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testApiKeyElementIdIsRequiredOption()
    {
        $this->factory->create($this->type, null, ['apiKeyElementId' => null]);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testApiKeyElementIdIsStringOption()
    {
        $this->factory->create($this->type, null, ['apiKeyElementId' => new \stdClass]);
    }

    public function testDefaultOptions()
    {
        $expected   = [
            'data_class' => UserApi::class,
            'csrf_protection' => ['enabled' => true, 'fieild_name' => 'apikey_token'],
            'intention'   => UserApiKeyGenType::NAME,
            'apiKeyElementId' => 'user-apikey-gen-elem'
        ];
        $form       = $this->factory->create($this->type, null, []);
        $defaults   = array_intersect_key($expected, $form->getConfig()->getOptions());

        $this->assertEquals($expected, $defaults);
    }

    public function testGetName()
    {
        $this->assertEquals(UserApiKeyGenType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(UserApiKeyGenType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    UserApiKeyGenType::NAME => new UserApiKeyGenType(),
                    UserApiKeyGenKeyType::NAME => new UserApiKeyGenKeyType()
                ],
                []
            )
        ];
    }
}
