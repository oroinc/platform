<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Entity;

use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;

class ImapEmailFolderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_STRING    = 'testString';
    const TEST_ID        = 123;
    const TEST_FLOAT     = 123.123;

    /** @var ImapEmailFolder */
    protected $entity;

    public function setUp()
    {
        $this->entity = new ImapEmailFolder();
    }

    /**
     * @dataProvider  getSetDataProvider
     *
     * @param string $property
     * @param mixed  $value
     * @param mixed  $expected
     */
    public function testSetGet($property, $value = null, $expected = null)
    {
        if ($value !== null) {
            call_user_func_array(array($this->entity, 'set' . ucfirst($property)), array($value));
        }

        $this->assertEquals($expected, call_user_func_array(array($this->entity, 'get' . ucfirst($property)), array()));
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        $emailFolder = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailFolder', array(), array(), '', false);
        return [
            'folder'        => ['folder', $emailFolder, $emailFolder],
            'uidValidity'        => ['uidValidity', self::TEST_ID, self::TEST_ID],
        ];
    }
}
