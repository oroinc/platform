<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit;

use Oro\Bundle\EmailBundle\Model\Condition\AutoResponseRulesExists;

class AutoResponseRulesExistsTest extends \PHPUnit_Framework_TestCase
{
    protected $autoResponseRuleRepository;

    protected $autoResponseRulesExists;
    
    public function setUp()
    {
        $this->autoResponseRuleRepository = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\AutoResponseRuleRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $registry->expects($this->any())
            ->method('getRepository')
            ->with('OroEmailBundle:AutoResponseRule')
            ->will($this->returnValue($this->autoResponseRuleRepository));

        $this->autoResponseRulesExists = new AutoResponseRulesExists($registry);
    }

    public function testEvaluateShouldReturnTrueIfRulesExists()
    {
        $this->autoResponseRuleRepository
            ->expects($this->once())
            ->method('rulesExists')
            ->will($this->returnValue(true));

        $this->assertTrue($this->autoResponseRulesExists->evaluate(null));
    }

    public function testEvaluateShouldReturnFalseIfRulesDoesntExists()
    {
        $this->autoResponseRuleRepository
            ->expects($this->once())
            ->method('rulesExists')
            ->will($this->returnValue(false));

        $this->assertFalse($this->autoResponseRulesExists->evaluate(null));
    }
}
