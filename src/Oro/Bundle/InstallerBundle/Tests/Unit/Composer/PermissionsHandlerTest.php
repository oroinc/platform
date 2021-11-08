<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Composer;

use Oro\Bundle\InstallerBundle\Composer\PermissionsHandler;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Process\Process;

class PermissionsHandlerTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var Process|\PHPUnit\Framework\MockObject\MockObject */
    private $process;

    /** @var string */
    private $directory;

    /** @var PermissionsHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->process = $this->createMock(Process::class);
        $this->directory = $this->getTempDir('permissions_handler');

        $this->handler = $this->getMockBuilder(PermissionsHandler::class)
            ->onlyMethods(['getProcess'])
            ->getMock();
    }

    public function testSetPermissionsSetFACL()
    {
        $this->process->expects($this->atLeastOnce())
            ->method('isSuccessful')
            ->willReturn(true);

        $this->handler->expects($this->exactly(3))
            ->method('getProcess')
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
                            ],
                            true
                        );
                    }
                )
            )
            ->willReturn($this->process);

        $this->process->expects($this->atLeastOnce())
            ->method('getOutput')
            ->willReturn(PermissionsHandler::USER);

        $this->handler->setPermissionsSetfacl($this->directory);
    }

    public function testSetPermissionsChmod()
    {
        $this->process->expects($this->atLeastOnce())
            ->method('isSuccessful')
            ->willReturn(true);

        $this->handler->expects($this->exactly(3))
            ->method('getProcess')
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
                            ],
                            true
                        );
                    }
                )
            )
            ->willReturn($this->process);

        $this->process->expects($this->atLeastOnce())
            ->method('getOutput')
            ->willReturn(PermissionsHandler::USER);

        $this->handler->setPermissionsChmod($this->directory);
    }

    public function testSetPermissions()
    {
        $this->handler->expects($this->any())
            ->method('getProcess')
            ->willReturn($this->process);

        $this->process->expects($this->atLeastOnce())
            ->method('isSuccessful')
            ->willReturn(false);

        $this->process->expects($this->atLeastOnce())
            ->method('getOutput')
            ->willReturn(PermissionsHandler::USER);

        $result = $this->handler->setPermissions($this->directory);

        $this->assertFalse($result);
    }
}
