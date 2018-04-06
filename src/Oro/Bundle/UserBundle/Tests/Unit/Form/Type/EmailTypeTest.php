<?php
namespace Oro\Bundle\UserBundle\Tests\Unit\Type;

use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Form\Type\EmailType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class EmailTypeTest extends FormIntegrationTestCase
{
    public function testBindValidData()
    {
        $formData = array(
            'email' => 'test@example.com',
        );

        $form = $this->factory->create(EmailType::class);

        $email = new Email();
        $email->setEmail($formData['email']);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($email, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
