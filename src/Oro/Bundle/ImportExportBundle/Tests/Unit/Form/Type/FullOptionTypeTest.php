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
     *
     * @dataProvider submitDataProvider
     */
    public function testSubmit($fieldType)
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
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'empty'    => [
                'fieldType' => null,
            ],
            'single'   => [
                'fieldType' => 'string',
            ],
            'relation' => [
                'fieldType' => 'oneToMany',
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
