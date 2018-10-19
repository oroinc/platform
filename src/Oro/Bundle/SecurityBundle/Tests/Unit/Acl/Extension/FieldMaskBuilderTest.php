<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Extension\FieldMaskBuilder;

class FieldMaskBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testViewGroup()
    {
        $this->assertEquals(
            FieldMaskBuilder::GROUP_VIEW,
            FieldMaskBuilder::MASK_VIEW_BASIC
            + FieldMaskBuilder::MASK_VIEW_LOCAL
            + FieldMaskBuilder::MASK_VIEW_DEEP
            + FieldMaskBuilder::MASK_VIEW_GLOBAL
            + FieldMaskBuilder::MASK_VIEW_SYSTEM
        );
    }

    public function testCreateGroup()
    {
        $this->assertEquals(
            FieldMaskBuilder::GROUP_CREATE,
            FieldMaskBuilder::MASK_CREATE_SYSTEM
        );
    }

    public function testEditGroup()
    {
        $this->assertEquals(
            FieldMaskBuilder::GROUP_EDIT,
            FieldMaskBuilder::MASK_EDIT_BASIC
            + FieldMaskBuilder::MASK_EDIT_LOCAL
            + FieldMaskBuilder::MASK_EDIT_DEEP
            + FieldMaskBuilder::MASK_EDIT_GLOBAL
            + FieldMaskBuilder::MASK_EDIT_SYSTEM
        );
    }

    public function testAllGroup()
    {
        $this->assertEquals(
            FieldMaskBuilder::GROUP_ALL,
            FieldMaskBuilder::GROUP_VIEW
            + FieldMaskBuilder::GROUP_EDIT
            + FieldMaskBuilder::GROUP_CREATE
        );
    }

    public function testRemoveServiceBits()
    {
        $this->assertEquals(
            FieldMaskBuilder::REMOVE_SERVICE_BITS,
            FieldMaskBuilder::GROUP_ALL
        );
    }

    public function testServiceBits()
    {
        $this->assertEquals(
            FieldMaskBuilder::SERVICE_BITS,
            ~FieldMaskBuilder::REMOVE_SERVICE_BITS
        );
    }

    public function testGetEmptyPattern()
    {
        $builder = new FieldMaskBuilder();

        $this->assertEquals(
            '(ECV) system:... global:.. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewBasic()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::MASK_VIEW_BASIC);

        $this->assertEquals(
            '(ECV) system:... global:.. deep:.. local:.. basic:.V',
            $builder->getPattern()
        );
    }

    public function testGetPatternEditBasic()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::MASK_EDIT_BASIC);

        $this->assertEquals(
            '(ECV) system:... global:.. deep:.. local:.. basic:E.',
            $builder->getPattern()
        );
    }

    public function testGetPatternBasic()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::GROUP_BASIC);

        $this->assertEquals(
            '(ECV) system:... global:.. deep:.. local:.. basic:EV',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewLocal()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::MASK_VIEW_LOCAL);

        $this->assertEquals(
            '(ECV) system:... global:.. deep:.. local:.V basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternEditLocal()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::MASK_EDIT_LOCAL);

        $this->assertEquals(
            '(ECV) system:... global:.. deep:.. local:E. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternLocal()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::GROUP_LOCAL);

        $this->assertEquals(
            '(ECV) system:... global:.. deep:.. local:EV basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewDeep()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::MASK_VIEW_DEEP);

        $this->assertEquals(
            '(ECV) system:... global:.. deep:.V local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternEditDeep()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::MASK_EDIT_DEEP);

        $this->assertEquals(
            '(ECV) system:... global:.. deep:E. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternDeep()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::GROUP_DEEP);

        $this->assertEquals(
            '(ECV) system:... global:.. deep:EV local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewGlobal()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::MASK_VIEW_GLOBAL);

        $this->assertEquals(
            '(ECV) system:... global:.V deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternEditGlobal()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::MASK_EDIT_GLOBAL);

        $this->assertEquals(
            '(ECV) system:... global:E. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternGlobal()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::GROUP_GLOBAL);

        $this->assertEquals(
            '(ECV) system:... global:EV deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewSystem()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::MASK_VIEW_SYSTEM);

        $this->assertEquals(
            '(ECV) system:..V global:.. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternEditSystem()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::MASK_EDIT_SYSTEM);

        $this->assertEquals(
            '(ECV) system:E.. global:.. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternCreateSystem()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::MASK_CREATE_SYSTEM);

        $this->assertEquals(
            '(ECV) system:.C. global:.. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternSystem()
    {
        $builder = new FieldMaskBuilder();
        $builder->add(FieldMaskBuilder::GROUP_SYSTEM);

        $this->assertEquals(
            '(ECV) system:ECV global:.. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }
}
