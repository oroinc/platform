<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityChangesetTransformer;

class EntityChangesetTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var EntityChangesetTransformer
     */
    protected $transformer;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->class = '\stdClass';
        $this->transformer = new EntityChangesetTransformer($this->doctrineHelper, $this->class);
    }

    public function testTransform()
    {
        $data = ['some random data'];
        $this->assertEquals($data, $this->transformer->transform($data));
    }

    /**
     * @dataProvider transformDataProvider
     *
     * @param mixed $expected
     * @param mixed $value
     */
    public function testReverseTransform($expected, $value)
    {
        if (!$expected) {
            $expected = new ArrayCollection();
        }

        $this->doctrineHelper->expects($expected->isEmpty() ? $this->never() : $this->exactly($expected->count()))
            ->method('getEntityReference')
            ->will(
                $this->returnCallback(
                    function () {
                        return $this->createDataObject(func_get_arg(1));
                    }
                )
            );

        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return [
            [null,[]],
            [[],[]],
            [
                new ArrayCollection([
                    '1' => ['entity' => $this->createDataObject(1), 'data' => ['test' => '123', 'test2' => 'val']],
                    '2' => ['entity' => $this->createDataObject(2), 'data' => ['test' => '12']]
                ]),
                new ArrayCollection([
                    '1' => ['data' => ['test' => '123', 'test2' => 'val']],
                    '2' => ['data' => ['test' => '12']]
                ])
            ]
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array", "string" given
     */
    public function testReverseTransformException()
    {
        $this->transformer->reverseTransform('test');
    }

    /**
     * @param int $id
     * @return \stdClass
     */
    public function createDataObject($id)
    {
        $obj = new \stdClass();
        $obj->id = $id;

        return $obj;
    }
}
