<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ImportExportBundle\Form\Type\FullOptionType;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class FullOptionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FullOptionType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new FullOptionType();
    }

    /**
     * @param string $fieldType
     * @param array $viewVars
     *
     * @dataProvider submitDataProvider
     */
    public function testSubmit($fieldType, array $viewVars)
    {
        $options = [];

        if ($fieldType) {
            $fieldConfigId = $this
                ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId')
                ->disableOriginalConstructor()
                ->getMock();

            $fieldConfigId
                ->expects($this->once())
                ->method('getFieldType')
                ->will($this->returnValue($fieldType));

            $options['config_id'] = $fieldConfigId;
        }

        $formMock = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $formView = new FormView();
        $this->type->finishView($formView, $formMock, $options);

        $this->assertEquals($viewVars, $formView->vars);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'empty'    => [
                'fieldType' => null,
                'viewVars' => [
                    'value' => null,
                    'attr' => []
                ]
            ],
            'single'   => [
                'fieldType' => 'string',
                'viewVars' => [
                    'disabled' => 'disabled',
                    'value' => null,
                    'attr' => []
                ]
            ],
            'ref-one' => [
                'fieldType' => 'ref-one',
                'viewVars' => [
                    'value' => null,
                    'attr' => []
                ]
            ],
            'oneToOne' => [
                'fieldType' => 'oneToOne',
                'viewVars' => [
                    'value' => null,
                    'attr' => []
                ]
            ],
            'manyToOne' => [
                'fieldType' => 'manyToOne',
                'viewVars' => [
                    'value' => null,
                    'attr' => []
                ]
            ],
            'ref-many' => [
                'fieldType' => 'ref-many',
                'viewVars' => [
                    'value' => null,
                    'attr' => []
                ]
            ],
            'oneToMany' => [
                'fieldType' => 'oneToMany',
                'viewVars' => [
                    'value' => null,
                    'attr' => []
                ]
            ],
            'manyToMany' => [
                'fieldType' => 'manyToMany',
                'viewVars' => [
                    'value' => null,
                    'attr' => []
                ]
            ],
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_importexport_full_option', $this->type->getName());
    }
}
