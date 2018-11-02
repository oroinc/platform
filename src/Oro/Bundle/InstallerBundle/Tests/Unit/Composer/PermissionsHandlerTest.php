<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Composer;

use Oro\Bundle\InstallerBundle\Composer\PermissionsHandler;
use Oro\Component\Testing\TempDirExtension;

class PermissionsHandlerTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /**
     * @var PermissionsHandler
     */
    protected $handler;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
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

        $this->directory = $this->getTempDir('permissions_handler');

        $this->handler = new PermissionsHandler();
        $this->handler->setProcess($this->process);
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
                                str_replace(
                                    [
                                        PermissionsHandler::VAR_USER,
                                        PermissionsHandler::VAR_GROUP,
                                        PermissionsHandler::VAR_PATH
                                    ],
                                    [
                                        PermissionsHandler::USER,
                                        PermissionsHandler::USER,
                                        $this->directory
                                    ],
                                    PermissionsHandler::SETFACL
                                )
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
                                str_replace(
                                    [PermissionsHandler::VAR_USER, PermissionsHandler::VAR_PATH],
                                    [PermissionsHandler::USER, $this->directory],
                                    PermissionsHandler::CHMOD
                                )
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
