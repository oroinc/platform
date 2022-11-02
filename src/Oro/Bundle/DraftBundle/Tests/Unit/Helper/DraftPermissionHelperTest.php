<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Helper;

use Oro\Bundle\DraftBundle\Helper\DraftPermissionHelper;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\UserBundle\Entity\User;

class DraftPermissionHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenAccessor */
    private $tokenAccessor;

    /** @var DraftPermissionHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessor::class);
        $this->helper = new DraftPermissionHelper($this->tokenAccessor);
    }

    public function testGeneratePermissions(): void
    {
        $user = new User();
        $this->tokenAccessor
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $source = new DraftableEntityStub();
        $permission = $this->helper->generatePermissions($source, 'VIEW');
        $this->assertEquals('VIEW_ALL_DRAFTS', $permission);

        $source
            ->setDraftOwner($user)
            ->setDraftUuid(UUIDGenerator::v4());
        $permission = $this->helper->generatePermissions($source, 'VIEW');
        $this->assertEquals('VIEW_DRAFT', $permission);
    }

    public function testGenerateOwnerPermission(): void
    {
        $permission = $this->helper->generateOwnerPermission('VIEW');
        $this->assertEquals('VIEW_DRAFT', $permission);
    }

    public function testGenerateGlobalPermission(): void
    {
        $permission = $this->helper->generateGlobalPermission('VIEW');
        $this->assertEquals('VIEW_ALL_DRAFTS', $permission);
    }
}
