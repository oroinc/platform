<?php
namespace Oro\Bundle\UserBundle\Tests\Unit\Type;

use Oro\Bundle\UserBundle\Entity\Status;
use Oro\Bundle\UserBundle\Form\Type\StatusType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class StatusTypeTest extends FormIntegrationTestCase
{
    public function testBindValidData()
    {
        $formData = array(
            'status' => 'test status',
        );

        $form = $this->factory->create(StatusType::class);

        $status = new Status();
        $status->setStatus($formData['status']);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($status, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
