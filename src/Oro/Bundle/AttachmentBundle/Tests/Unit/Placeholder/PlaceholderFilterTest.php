<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\AttachmentBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentConfig;

    /** @var PlaceholderFilter */
    protected $filter;

    protected function setUp()
    {
        $this->attachmentConfig = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new PlaceholderFilter($this->attachmentConfig);
    }

    /**
     * @param boolean $return
     * @dataProvider configResultProvider
     */
    public function testIsAttachmentAssociationEnabled($return)
    {
        $entity = $this->getMock('\stdClass');

        $this->attachmentConfig->expects($this->once())
            ->method('isAttachmentAssociationEnabled')
            ->with($entity)
            ->willReturn($return);

        $actual = $this->filter->isAttachmentAssociationEnabled($entity);
        $this->assertEquals($return, $actual);
    }

    /**
     * @return array
     */
    public function configResultProvider()
    {
        return [
            ['return' => true],
            ['return' => false],
        ];
    }
}
