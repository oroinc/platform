<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Tests\Functional;

/**
 * @dbIsolationPerTest
 */
class UserOperationsAclTest extends UserOperationAclTestCase
{
    public function testEnableUserOfOwnBusinessUnit(): void
    {
        $this->getUser(self::USER_OF_OWN_BUSINESS_UNIT)->setEnabled(false);
        $this->flush();

        $flashMessages = $this->executeOperation('user_enable', self::USER_OF_OWN_BUSINESS_UNIT);

        self::assertResponseStatusCodeEquals($this->client->getResponse(), 200);
        $user = $this->getUser(self::USER_OF_OWN_BUSINESS_UNIT);
        self::assertSame(['success' => [sprintf('User #%d enabled.', $user->getId())]], $flashMessages);
        self::assertTrue($user->isEnabled());
    }

    public function testDisableUserOfOwnBusinessUnit(): void
    {
        $flashMessages = $this->executeOperation('user_disable', self::USER_OF_OWN_BUSINESS_UNIT);

        self::assertResponseStatusCodeEquals($this->client->getResponse(), 200);
        $user = $this->getUser(self::USER_OF_OWN_BUSINESS_UNIT);
        self::assertSame(['success' => [sprintf('User #%d disabled.', $user->getId())]], $flashMessages);
        self::assertFalse($user->isEnabled());
    }

    public function testCannotEnableUserOfAnotherBusinessUnit(): void
    {
        $this->getUser(self::USER_OF_ANOTHER_BUSINESS_UNIT)->setEnabled(false);
        $this->flush();

        $this->executeOperation('user_enable', self::USER_OF_ANOTHER_BUSINESS_UNIT);

        self::assertResponseStatusCodeEquals($this->client->getResponse(), 403);
        self::assertFalse($this->getUser(self::USER_OF_ANOTHER_BUSINESS_UNIT)->isEnabled());
    }

    public function testCannotDisableUserOfAnotherBusinessUnit(): void
    {
        $this->executeOperation('user_disable', self::USER_OF_ANOTHER_BUSINESS_UNIT);

        self::assertResponseStatusCodeEquals($this->client->getResponse(), 403);
        self::assertTrue($this->getUser(self::USER_OF_ANOTHER_BUSINESS_UNIT)->isEnabled());
    }
}
