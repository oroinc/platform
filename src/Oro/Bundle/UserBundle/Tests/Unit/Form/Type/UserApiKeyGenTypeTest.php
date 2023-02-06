<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Form\Type\UserApiKeyGenKeyType;
use Oro\Bundle\UserBundle\Form\Type\UserApiKeyGenType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class UserApiKeyGenTypeTest extends FormIntegrationTestCase
{
    private UserApiKeyGenType $formType;

    protected function setUp(): void
    {
        $this->formType = new UserApiKeyGenType();
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [$this->formType, new UserApiKeyGenKeyType()],
                []
            )
        ];
    }

    public function testSubmit()
    {
        $userApi = new UserApi();
        $form = $this->factory->create(UserApiKeyGenType::class, $userApi);
        $form->submit([]);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($userApi, $form->getData());
    }

    public function testApiKeyElementIdIsRequiredOption()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->factory->create(UserApiKeyGenType::class, null, ['apiKeyElementId' => null]);
    }

    public function testApiKeyElementIdIsStringOption()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->factory->create(UserApiKeyGenType::class, null, ['apiKeyElementId' => new \stdClass]);
    }

    public function testDefaultOptions()
    {
        $expected = [
            'data_class' => UserApi::class,
            'csrf_protection' => ['enabled' => true, 'fieild_name' => 'apikey_token'],
            'csrf_token_id' => UserApiKeyGenType::NAME,
            'apiKeyElementId' => 'user-apikey-gen-elem'
        ];
        $form = $this->factory->create(UserApiKeyGenType::class, null, []);
        $defaults = array_intersect_key($expected, $form->getConfig()->getOptions());

        $this->assertEquals($expected, $defaults);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_user_apikey_gen', $this->formType->getBlockPrefix());
    }
}
