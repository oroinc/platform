<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\ActionButtonGroupLabelProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ActionButtonGroupLabelProviderTest extends TestCase
{
    public function testGetLabelWithDefaultGroupAndWithoutEntityClass(): void
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

    public function testGetLabelWithoutEntityClass(): void
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

    public function testGetLabelWithoutEntityPlaceholder(): void
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

    public function testGetLabel(): void
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
