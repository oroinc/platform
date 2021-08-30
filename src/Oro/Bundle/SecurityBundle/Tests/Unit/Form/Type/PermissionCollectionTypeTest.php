<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SecurityBundle\Form\Type\PermissionCollectionType;
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
        $view = $this->createMock(FormView::class);
        $form = $this->createMock(FormInterface::class);

        $privileges_config = ['permissions' => ['VIEW', 'CREATE']];
        $options = ['entry_options' => ['privileges_config' => $privileges_config]];

        (new PermissionCollectionType())->buildView($view, $form, $options);

        $this->assertSame($privileges_config, $view->vars['privileges_config']);
    }
}
