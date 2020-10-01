<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\TestCase;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\Oauth2ManagerInterface;
use Oro\Bundle\ImapBundle\Manager\OAuth2ManagerRegistry;
use Oro\Bundle\ImapBundle\Tests\Unit\Stub\Form\Type\ConfigurationTestType;
use PHPUnit\Framework\MockObject\MockObject;

trait OauthManagerRegistryAwareTrait
{
    /**
     * @return MockObject|OAuth2ManagerRegistry
     */
    protected function getManagerRegistryMock(): MockObject
    {
        /** @var MockObject $mock */
        $mock = $this->getMockBuilder(OAuth2ManagerRegistry::class)->getMock();
        $mock->expects($this->any())
            ->method('getManager')
            ->willReturnMap($map = $this->getResultMap());
        $mock->expects($this->any())
            ->method('hasManager')
            ->willReturnMap(array_map(function (array $datum) {
                return [
                    $datum[0],
                    true
                ];
            }, $map));

        $managers = array_map(function (array $datum) {
            /** @var Oauth2ManagerInterface $manager */
            $manager = $datum[1];
            return $manager;
        }, $map);

        $mock->expects($this->any())
            ->method('getTypes')
            ->willReturn(array_map(function (Oauth2ManagerInterface $manager) {
                return $manager->getType();
            }, $managers));

        $mock->expects($this->any())
            ->method('getManagers')
            ->willReturn(array_values($managers));

        return $mock;
    }

    /**
     * @return array
     */
    protected function getResultMap(): array
    {
        $results = [];
        for ($i = 1; $i <= 3; $i++) {
            $type = sprintf('manager_type_%d', $i);
            $mock = $this->getManagerMock($type);
            $results[] = [
                $type,
                $mock
            ];
        }

        return $results;
    }

    /**
     * Returns mocked manager instance of \Oro\Bundle\ImapBundle\Manager\Oauth2ManagerInterface
     * for certain type
     *
     * @param string $type
     * @return MockObject|Oauth2ManagerInterface
     */
    protected function getManagerMock(string $type): MockObject
    {
        $mock = $this->getMockBuilder(Oauth2ManagerInterface::class)->getMock();
        $mock->expects($this->any())
            ->method('getType')
            ->willReturn($type);
        $mock->expects($this->any())
            ->method('getConnectionFormTypeClass')
            ->willReturn(ConfigurationTestType::class);
        $mock->expects($this->any())
            ->method('getAuthMode')
            ->willReturn('XOAUTH2');
        $mock->expects($this->any())
            ->method('isOAuthEnabled')
            ->willReturn(true);
        ;

        return $mock;
    }

    /**
     * Returns \Oro\Bundle\ImapBundle\Entity\UserEmailOrigin mock instance
     * for certain type
     *
     * @param string $type
     * @return MockObject|UserEmailOrigin
     */
    protected function getEmailOriginMock(string $type): MockObject
    {
        $mock = $this->getMockBuilder(UserEmailOrigin::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->any())
            ->method('getAccountType')
            ->willReturn($type);

        return $mock;
    }
}
