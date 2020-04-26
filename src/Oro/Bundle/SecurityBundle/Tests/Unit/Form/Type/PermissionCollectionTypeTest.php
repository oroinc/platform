<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SecurityBundle\Form\Type\PermissionCollectionType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;

class PermissionCollectionTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testGetParent()
    {
        $formType = new PermissionCollectionType();

        $this->assertEquals(CollectionType::class, $formType->getParent());
    }

    public function testBuildView()
    {
        /** @var FormView|MockObject $view */
        $view = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var FormInterface|MockObject $form */
        $form = $this->getMockBuilder(FormInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $privileges_config = ['permissions' => ['VIEW', 'CREATE']];
        $options = ['entry_options' => ['privileges_config' => $privileges_config]];

        (new PermissionCollectionType())->buildView($view, $form, $options);

        $this->assertSame($privileges_config, $view->vars['privileges_config']);
    }
}
