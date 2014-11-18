<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Handler;

use Oro\Bundle\SoapBundle\Handler\TotalHeaderHandler;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestApiReadInterface;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;

class TotalHeaderHandlerTest extends \PHPUnit_Framework_TestCase
{
    use ContextAwareTest;

    /** @var TotalHeaderHandler */
    protected $handler;

    protected function setUp()
    {
        $this->handler = new TotalHeaderHandler(new CountQueryBuilderOptimizer());
    }

    protected function tearDown()
    {
        unset($this->handler);
    }

    public function testSupportsWithValidQueryAndAction()
    {
        $context = $this->createContext(null, null, null, RestApiReadInterface::ACTION_LIST);
        $context->set('query', $this->getMockForAbstractClass('Doctrine\ORM\AbstractQuery', [], '', false));

        $this->assertTrue($this->handler->supports($context));
    }

    public function testNotSupportsWithAnotherThenListActions()
    {
        $context = $this->createContext(null, null, null, RestApiReadInterface::ACTION_READ);
        $context->set('query', $this->getMockForAbstractClass('Doctrine\ORM\AbstractQuery', [], '', false));

        $this->assertFalse($this->handler->supports($context));
    }

    public function testSupportsWithEntityManagerAwareController()
    {
        $context = $this->createContext(
            $this->getMock('Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface'),
            null,
            null,
            RestApiReadInterface::ACTION_LIST
        );

        $this->assertTrue($this->handler->supports($context));
    }

    public function testNotSupportsWithAnotherThenListActionsEvenControllerIsEntityManagerAwareController()
    {
        $context = $this->createContext(
            $this->getMock('Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface'),
            null,
            null,
            RestApiReadInterface::ACTION_READ
        );

        $this->assertFalse($this->handler->supports($context));
    }
}
