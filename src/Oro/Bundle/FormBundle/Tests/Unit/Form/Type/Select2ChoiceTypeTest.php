<?php

/*
 * This file is part of the GenemuFormBundle package.
 *
 * (c) Olivier Chauvel <olivier@generation-multiple.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2Type;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\TypeTestCase;

class Select2ChoiceTypeTest extends TypeTestCase
{
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([
                new Select2Type(ChoiceType::class, 'prefix_choice'),
            ], [])
        ];
    }

    public function testConstructorAffectsParentType()
    {
        $form = $this->factory->create(Select2Type::class);

        $this->assertEquals(ChoiceType::class, get_class($form->getConfig()->getType()->getParent()->getInnerType()));
    }

    public function testSelectSingle()
    {
        $form = $this->factory->create(
            Select2Type::class,
            null,
            [
                'choices' => [
                    'Foo' => 'foo',
                    'Bar' => 'bar'
                ]
            ]
        );

        $form->submit('foo');

        $this->assertEquals('foo', $form->getData());
    }

    public function testSelectMultiple()
    {
        $form = $this->factory->create(
            Select2Type::class,
            null,
            [
                'choices' => [
                    'Foo' => 'foo',
                    'Bar' => 'bar'
                ],
                'multiple' => true
            ]
        );

        $form->submit(['foo']);

        $this->assertEquals(['foo'], $form->getData());
        $this->assertEquals(['foo'], $form->getViewData());
    }
}
