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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Test\TypeTestCase;

class Select2HiddenTypeTest extends TypeTestCase
{
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([
                new Select2Type(HiddenType::class, 'prefix_hidden')
            ], [])
        ];
    }

    public function testConstructorAffectsParentType()
    {
        $form = $this->factory->create(Select2Type::class);

        $this->assertEquals(HiddenType::class, get_class($form->getConfig()->getType()->getParent()->getInnerType()));
    }

    public function testHiddenSingle()
    {
        $form = $this->factory->create(Select2Type::class);

        $form->submit('Touti');

        $this->assertEquals('Touti', $form->getData());
        $this->assertEquals('Touti', $form->getViewData());
    }

    public function testHiddenMultiple()
    {
        $form = $this->factory->create(
            Select2Type::class,
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
            Select2Type::class,
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
