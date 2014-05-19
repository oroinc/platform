<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Composer;

use Symfony\Component\Filesystem\Filesystem;

use Oro\Bundle\InstallerBundle\Composer\PermissionsHandler;

class PermissionsHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PermissionsHandler
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $process;

    /**
     * @var string
     */
    protected $directory;

    protected function setUp()
    {
        $this->process = $this
            ->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . time();

        $fs = new Filesystem();
        $fs->mkdir($this->directory);

        $this->handler = new PermissionsHandler();
        $this->handler->setProcess($this->process);
    }

    protected function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove($this->directory);
    }

    public function testSetPermissionsSetfacl()
    {
        $this->process
            ->expects($this->atLeastOnce())
            ->method('isSuccessful')
            ->will($this->returnValue(true));

        $this->process
            ->expects($this->any())
            ->method('setCommandLine')
            ->with(
                $this->callback(
                    function ($argument) {
                        return in_array(
                            $argument,
                            [
                                PermissionsHandler::PS_AUX,
                                sprintf(
                                    PermissionsHandler::SETFACL,
                                    PermissionsHandler::SETFACL_MODE_NONE,
                                    PermissionsHandler::USER,
                                    PermissionsHandler::USER,
                                    $this->directory
                                ),
                                sprintf(
                                    PermissionsHandler::SETFACL,
                                    PermissionsHandler::SETFACL_MODE_NONE,
                                    PermissionsHandler::USER,
                                    PermissionsHandler::USER,
                                    $this->directory
                                ),
                            ]
                        );
                    }
                )
            );

        $this->process
            ->expects($this->exactly(3))
            ->method('setCommandLine');

        $this->process
            ->expects($this->atLeastOnce())
            ->method('getOutput')
            ->will($this->returnValue(PermissionsHandler::USER));

        $this->handler->setPermissionsSetfacl($this->directory);
    }

    public function testSetPermissionsChmod()
    {
        $this->process
            ->expects($this->atLeastOnce())
            ->method('isSuccessful')
            ->will($this->returnValue(true));

        $this->process
            ->expects($this->any())
            ->method('setCommandLine')
            ->with(
                $this->callback(
                    function ($argument) {
                        return in_array(
                            $argument,
                            [
                                PermissionsHandler::PS_AUX,
                                sprintf(
                                    PermissionsHandler::CHMOD,
                                    PermissionsHandler::USER,
                                    $this->directory
                                ),
                            ]
                        );
                    }
                )
            );

        $this->process
            ->expects($this->exactly(2))
            ->method('setCommandLine');

        $this->process
            ->expects($this->atLeastOnce())
            ->method('getOutput')
            ->will($this->returnValue(PermissionsHandler::USER));

        $this->handler->setPermissionsChmod($this->directory);
    }

    public function testSetPermissions()
    {
        $this->process
            ->expects($this->atLeastOnce())
            ->method('isSuccessful')
            ->will($this->returnValue(false));

        $this->process
            ->expects($this->atLeastOnce())
            ->method('getOutput')
            ->will($this->returnValue(PermissionsHandler::USER));

        $result = $this->handler->setPermissions($this->directory);

        $this->assertFalse($result);
    }
}
