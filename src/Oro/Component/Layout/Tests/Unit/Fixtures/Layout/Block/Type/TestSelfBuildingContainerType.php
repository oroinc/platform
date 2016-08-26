<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;

class TestSelfBuildingContainerType extends AbstractContainerType
{
    /**
     * {@inheritdoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, Options $options)
    {
        $id = $builder->getId();
        $builder->getLayoutManipulator()
            ->add($id . '_logo', $id, 'logo');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'test_self_building_container';
    }
}
