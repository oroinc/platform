<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class EntityFilterTypeTest extends AbstractTypeTestCase
{
    /**
     * @var EntityFilterType
     */
    private $type;

    protected function setUp(): void
    {
        $translator = $this->createMockTranslator();

        $registry = $this->getMockForAbstractClass('Doctrine\Persistence\ManagerRegistry', array(), '', false);

        $types = array(
            new FilterType($translator),
            new ChoiceFilterType($translator),
            new EntityType($registry)
        );

        $this->formExtensions[] = new CustomFormExtension($types);

        parent::setUp();

        $this->type = new EntityFilterType($translator);
    }

    /**
     * @return EntityFilterType
     */
    protected function getTestFormType()
    {
        return $this->type;
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceFilterType::class, $this->type->getParent());
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionsDataProvider()
    {
        return array(
            array(
                'defaultOptions' => array(
                    'field_type' => EntityType::class,
                    'field_options' => array(),
                    'translatable'  => false,
                )
            )
        );
    }

    /**
     * @dataProvider bindDataProvider
     */
    public function testBindData(
        array $bindData,
        array $formData,
        array $viewData,
        array $customOptions = array()
    ) {
        // bind method should be tested in functional test
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider()
    {
        return array(
            'empty' => array(
                'bindData' => array(),
                'formData' => array(),
                'viewData' => array(),
            ),
        );
    }
}
