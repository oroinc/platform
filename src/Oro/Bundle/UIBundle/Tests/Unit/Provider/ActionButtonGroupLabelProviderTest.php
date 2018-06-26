<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\ActionButtonGroupLabelProvider;

class ActionButtonGroupLabelProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetLabelWithDefaultGroupAndWithoutEntityClass()
    {
        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $provider   = new ActionButtonGroupLabelProvider($translator);

        $translator->expects($this->once())
            ->method('trans')
            ->with(ActionButtonGroupLabelProvider::DEFAULT_LABEL)
            ->will($this->returnValue('Label'));

        $this->assertEquals(
            'Label',
            $provider->getLabel(['groupName' => ActionButtonGroupLabelProvider::DEFAULT_GROUP])
        );
    }

    public function testGetLabelWithoutEntityClass()
    {
        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $provider   = new ActionButtonGroupLabelProvider($translator);

        $translator->expects($this->once())
            ->method('trans')
            ->with(sprintf(ActionButtonGroupLabelProvider::DEFAULT_GROUP_LABEL, 'test_group'))
            ->will($this->returnValue('Label'));

        $this->assertEquals(
            'Label',
            $provider->getLabel(['groupName' => 'test_group'])
        );
    }

    public function testGetLabelWithoutEntityPlaceholder()
    {
        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $provider   = new ActionButtonGroupLabelProvider($translator);

        $translator->expects($this->once())
            ->method('trans')
            ->with(ActionButtonGroupLabelProvider::DEFAULT_LABEL)
            ->will($this->returnValue('Label'));

        $this->assertEquals(
            'Label',
            $provider->getLabel(
                [
                    'groupName'   => ActionButtonGroupLabelProvider::DEFAULT_GROUP,
                    'entityClass' => 'Acme\Bundle\ProductBundle\Entity\Product'
                ]
            )
        );
    }

    public function testGetLabel()
    {
        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $provider   = new ActionButtonGroupLabelProvider($translator);

        $translator->expects($this->at(0))
            ->method('trans')
            ->with(ActionButtonGroupLabelProvider::DEFAULT_LABEL)
            ->will($this->returnValue(ActionButtonGroupLabelProvider::ENTITY_NAME_PLACEHOLDER . ' Label'));
        $translator->expects($this->at(1))
            ->method('trans')
            ->with('acme.product.entity_label')
            ->will($this->returnValue('Product'));

        $this->assertEquals(
            'Product Label',
            $provider->getLabel(
                [
                    'groupName'   => ActionButtonGroupLabelProvider::DEFAULT_GROUP,
                    'entityClass' => 'Acme\Bundle\ProductBundle\Entity\Product'
                ]
            )
        );
    }
}
