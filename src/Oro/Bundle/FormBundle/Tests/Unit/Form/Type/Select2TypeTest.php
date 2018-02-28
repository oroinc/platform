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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Test\TypeTestCase;

class Select2TypeTest extends TypeTestCase
{
    public function testConstructorAffectsParentType()
    {
        $form = $this->factory->create(new Select2Type(HiddenType::class, 'prefix_hidden'));

        $this->assertEquals(HiddenType::class, get_class($form->getConfig()->getType()->getParent()->getInnerType()));
    }

    public function testSelectSingle()
    {
        $form = $this->factory->create(
            new Select2Type(ChoiceType::class, 'prefix_choice'),
            null,
            [
                'choices' => [
                    'foo' => 'Foo',
                    'bar' => 'Bar'
                ]
            ]
        );

        $form->submit('foo');

        $this->assertEquals('foo', $form->getData());
    }

    public function testSelectMultiple()
    {
        $form = $this->factory->create(
            new Select2Type(ChoiceType::class, 'prefix_choice'),
            null,
            [
                'choices' => [
                    'foo' => 'Foo',
                    'bar' => 'Bar'
                ],
                'multiple' => true
            ]
        );

        $form->submit(['foo']);

        $this->assertEquals(['foo'], $form->getData());
        $this->assertEquals(['foo'], $form->getViewData());
    }

    public function testHiddenSingle()
    {
        $form = $this->factory->create(new Select2Type(HiddenType::class, 'prefix_hidden'));

        $form->submit('Touti');

        $this->assertEquals('Touti', $form->getData());
        $this->assertEquals('Touti', $form->getViewData());
    }

    public function testHiddenMultiple()
    {
        $form = $this->factory->create(
            new Select2Type(HiddenType::class, 'prefix_hidden'),
            null,
            [
                'configs' => [
                    'multiple' => true
                ]
            ]
        );

        $form->submit('Touti,Douti');

        $this->assertEquals(['Touti', 'Douti'], $form->getData());
        $this->assertEquals('Touti,Douti', $form->getViewData());
    }

    public function testHiddenMultipleDefault()
    {
        $form = $this->factory->create(
            new Select2Type(HiddenType::class, 'prefix_hidden'),
            ['Touti', 'Douti'],
            [
                'configs' => [
                    'multiple' => true
                ]
            ]
        );

        $this->assertEquals(['Touti', 'Douti'], $form->getData());
        $this->assertEquals('Touti,Douti', $form->getViewData());
    }
}
