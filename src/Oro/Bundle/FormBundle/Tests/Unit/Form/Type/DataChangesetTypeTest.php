<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;

class DataChangesetTypeTest extends FormIntegrationTestCase
{
    /**
     * @var DataChangesetType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new DataChangesetType();
    }

    public function testGetName()
    {
        $this->assertEquals(DataChangesetType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('hidden', $this->type->getParent());
    }

    /**
     * @dataProvider submitProvider
     *
     * @param $defaultData
     * @param $viewData
     * @param $submittedData
     * @param ArrayCollection $expected
     */
    public function testSubmit($defaultData, $viewData, $submittedData, ArrayCollection $expected)
    {
        $form = $this->factory->create($this->type, $defaultData);

        $this->assertFalse($form->getConfig()->getOption('mapped'));

        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $data = $form->getData();

        $this->assertEquals($expected, $data);
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            [
                'defaultData' => new ArrayCollection([
                    '1' => ['data' => ['test' => '123', 'test2' => 'val']],
                    '2' => ['data' => ['test' => '12']]
                ]),
                'viewData' =>  json_encode([
                    '1' => ['test' => '123', 'test2' => 'val'],
                    '2' => ['test' => '12']
                ]),
                'submittedData'  => json_encode([
                    '1' => ['test' => '321', 'test2' => 'val'],
                    '2' => ['test' => '21']
                ]),
                'expected' => new ArrayCollection([
                    '1' => ['data' => ['test' => '321', 'test2' => 'val']],
                    '2' => ['data' => ['test' => '21']]
                ]),
            ]
        ];
    }
}
