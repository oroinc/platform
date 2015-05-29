<?php
namespace Oro\Bundle\LDAPBundle\Tests\Unit\ImportExport;

use Oro\Bundle\LDAPBundle\ImportExport\LdapUserImportProcessor;
use Oro\Bundle\SSOBundle\Tests\Unit\Stub\TestingUser;

class LdapUserImportProcessorTest extends \PHPUnit_Framework_TestCase
{
    use MocksChannelAndContext;

    private $processor;

    public function setUp()
    {
        $this->mockChannel();
        $this->mockContext();
        $this->mockContextRegistry();
        $this->mockContextMediator();
        $this->mockUserManager();
        $this->mockLdapManager();
        $this->mockChannelManagerProvider();

        $this->processor = new LdapUserImportProcessor(
            $this->userManager,
            $this->contextRegistry,
            $this->contextMediator,
            $this->managerProvider
        );

        $se = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor->setStepExecution($se);
        $this->processor->initialize();
    }

    public function testProcessExistingUser()
    {
        $user = new TestingUser();
        $item = ['username_attr' => 'username_value'];

        $this->userManager->expects($this->once())
            ->method('findUserByUsername')
            ->with($this->equalTo('username_value'))
            ->will($this->returnValue($user));

        $this->ldapManager->expects($this->once())
            ->method('hydrate')
            ->with($this->equalTo($user), $this->equalTo($item));

        $this->context->expects($this->once())
            ->method('incrementUpdateCount');

        $this->processor->process($item);
    }

    public function testProcessNewUser()
    {
        $user = new TestingUser();
        $item = ['username_attr' => 'username_value'];

        $this->userManager->expects($this->once())
            ->method('findUserByUsername')
            ->with($this->equalTo('username_value'))
            ->will($this->returnValue(null));

        $this->userManager->expects($this->once())
            ->method('createUser')
            ->will($this->returnValue($user));

        $this->ldapManager->expects($this->once())
            ->method('hydrate')
            ->with($this->equalTo($user), $this->equalTo($item));

        $this->context->expects($this->once())
            ->method('incrementAddCount');

        $this->processor->process($item);
    }
}
