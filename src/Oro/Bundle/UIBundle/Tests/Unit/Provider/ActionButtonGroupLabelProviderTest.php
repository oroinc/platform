<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\ActionButtonGroupLabelProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class ActionButtonGroupLabelProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetLabelWithDefaultGroupAndWithoutEntityClass()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $provider = new ActionButtonGroupLabelProvider($translator);

        $translator->expects($this->once())
            ->method('trans')
            ->with(ActionButtonGroupLabelProvider::DEFAULT_LABEL)
            ->willReturn('Label');

        $this->assertEquals(
            'Label',
            $provider->getLabel(['groupName' => ActionButtonGroupLabelProvider::DEFAULT_GROUP])
        );
    }

    public function testGetLabelWithoutEntityClass()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $provider = new ActionButtonGroupLabelProvider($translator);

        $translator->expects($this->once())
            ->method('trans')
            ->with(sprintf(ActionButtonGroupLabelProvider::DEFAULT_GROUP_LABEL, 'test_group'))
            ->willReturn('Label');

        $this->assertEquals(
            'Label',
            $provider->getLabel(['groupName' => 'test_group'])
        );
    }

    public function testGetLabelWithoutEntityPlaceholder()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $provider = new ActionButtonGroupLabelProvider($translator);

        $translator->expects($this->once())
            ->method('trans')
            ->with(ActionButtonGroupLabelProvider::DEFAULT_LABEL)
            ->willReturn('Label');

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
        $translator = $this->createMock(TranslatorInterface::class);
        $provider = new ActionButtonGroupLabelProvider($translator);

        $translator->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(
                [ActionButtonGroupLabelProvider::DEFAULT_LABEL],
                ['acme.product.entity_label']
            )
            ->willReturnOnConsecutiveCalls(
                ActionButtonGroupLabelProvider::ENTITY_NAME_PLACEHOLDER . ' Label',
                'Product'
            );

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
