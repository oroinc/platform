<?php
namespace Oro\Bundle\LDAPBundle\Tests\Unit\ImportExport;

use Oro\Bundle\LDAPBundle\ImportExport\LdapUserWriter;
use Oro\Bundle\SSOBundle\Tests\Unit\Stub\TestingUser;

class LdapUserWriterTest extends \PHPUnit_Framework_TestCase
{
    use MocksChannelAndContext;

    private $writer;

    public function setUp()
    {
        $this->mockChannel();
        $this->mockContext();
        $this->mockContextRegistry();
        $this->mockContextMediator();
        $this->mockUserManager();
        $this->mockLdapManager();
        $this->mockChannelManagerProvider();

        $this->writer = new LdapUserWriter(
            $this->userManager,
            $this->contextRegistry,
            $this->contextMediator,
            $this->managerProvider
        );

        $se = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $this->writer->setStepExecution($se);
    }

    public function testWriteNothing()
    {
        $items = [];

        $this->ldapManager->expects($this->never())
            ->method('save');

        $this->writer->write($items);
    }

    public function testWriteSingleExisting()
    {
        $items = [
            $firstUser = new TestingUser(),
        ];

        $this->ldapManager->expects($this->once())
            ->method('exists')
            ->with($this->equalTo($firstUser))
            ->will($this->returnValue(true));

        $this->context->expects($this->once())
            ->method('incrementUpdateCount');

        $this->ldapManager->expects($this->once())
            ->method('save');

        $this->writer->write($items);
    }

    public function testWriteSingleNonExisting()
    {
        $items = [
            $firstUser = new TestingUser(),
        ];

        $this->ldapManager->expects($this->once())
            ->method('exists')
            ->with($this->equalTo($firstUser))
            ->will($this->returnValue(false));

        $this->context->expects($this->once())
            ->method('incrementAddCount');

        $this->ldapManager->expects($this->once())
            ->method('save');

        $this->writer->write($items);
    }

    public function testWriteMultipleExisting()
    {
        $firstUser = new TestingUser();
        $firstUser->setId(1);
        $secondUser = new TestingUser();
        $secondUser->setId(2);
        $thirdUser = new TestingUser();
        $thirdUser->setId(3);
        $fourthUser = new TestingUser();
        $fourthUser->setId(4);
        $items = [
            $firstUser,
            $secondUser,
            $thirdUser,
            $fourthUser,
        ];

        $this->ldapManager->expects($this->exactly(count($items)))
            ->method('exists')
            ->withConsecutive(
                [$this->equalTo($firstUser)],
                [$this->equalTo($secondUser)],
                [$this->equalTo($thirdUser)],
                [$this->equalTo($fourthUser)]
            )
            ->will($this->returnValue(true));

        $this->context->expects($this->exactly(count($items)))
            ->method('incrementUpdateCount');

        $this->ldapManager->expects($this->exactly(count($items)))
            ->method('save')
            ->withConsecutive(
                [$this->equalTo($firstUser)],
                [$this->equalTo($secondUser)],
                [$this->equalTo($thirdUser)],
                [$this->equalTo($fourthUser)]
            );

        $this->writer->write($items);
    }

    public function testWriteMultipleNonExisting()
    {
        $firstUser = new TestingUser();
        $firstUser->setId(1);
        $secondUser = new TestingUser();
        $secondUser->setId(2);
        $thirdUser = new TestingUser();
        $thirdUser->setId(3);
        $fourthUser = new TestingUser();
        $fourthUser->setId(4);
        $items = [
            $firstUser,
            $secondUser,
            $thirdUser,
            $fourthUser,
        ];

        $this->ldapManager->expects($this->exactly(count($items)))
            ->method('exists')
            ->withConsecutive(
                [$this->equalTo($firstUser)],
                [$this->equalTo($secondUser)],
                [$this->equalTo($thirdUser)],
                [$this->equalTo($fourthUser)]
            )
            ->will($this->returnValue(false));

        $this->context->expects($this->exactly(count($items)))
            ->method('incrementAddCount');

        $this->ldapManager->expects($this->exactly(count($items)))
            ->method('save')
            ->withConsecutive(
                [$this->equalTo($firstUser)],
                [$this->equalTo($secondUser)],
                [$this->equalTo($thirdUser)],
                [$this->equalTo($fourthUser)]
            );

        $this->writer->write($items);
    }

    public function testWriteMultipleMixed()
    {
        $firstUser = new TestingUser();
        $firstUser->setId(1);
        $secondUser = new TestingUser();
        $secondUser->setId(2);
        $thirdUser = new TestingUser();
        $thirdUser->setId(3);
        $fourthUser = new TestingUser();
        $fourthUser->setId(4);
        $items = [
            $firstUser,
            $secondUser,
            $thirdUser,
            $fourthUser,
        ];

        $this->ldapManager->expects($this->exactly(count($items)))
            ->method('exists')
            ->withConsecutive(
                [$this->equalTo($firstUser)],
                [$this->equalTo($secondUser)],
                [$this->equalTo($thirdUser)],
                [$this->equalTo($fourthUser)]
            )
            ->will($this->onConsecutiveCalls(false, true, true, false));

        $this->context->expects($this->exactly(2))
            ->method('incrementUpdateCount');

        $this->context->expects($this->exactly(2))
            ->method('incrementAddCount');

        $this->ldapManager->expects($this->exactly(count($items)))
            ->method('save')
            ->withConsecutive(
                [$this->equalTo($firstUser)],
                [$this->equalTo($secondUser)],
                [$this->equalTo($thirdUser)],
                [$this->equalTo($fourthUser)]
            );

        $this->writer->write($items);
    }
}
