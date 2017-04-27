<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Psr\Log\AbstractLogger;

class EntityAliasesTest extends WebTestCase
{
    /** @var array */
    protected $messages;

    /** @var AbstractLogger|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    protected function setUp()
    {
        $this->initClient();

        $this->messages = [];

        $this->logger = $this->getMockBuilder(AbstractLogger::class)
            ->setMethods(['log'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger->expects($this->any())
            ->method('log')
            ->willReturnCallback(
                function ($level, $message, array $context = []) {
                    $this->messages[] = $message;
                }
            );

        $this->entityAliasResolver = $this->getContainer()->get('oro_test.entity_alias_resolver');
        $this->entityAliasResolver->setLogger($this->logger);
    }

    public function testAliasErrors()
    {
        $this->entityAliasResolver->clearCache();
        $this->entityAliasResolver->getAll();

        $this->assertEmpty($this->messages, implode("\n", $this->messages));
    }
}
