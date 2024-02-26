<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Controller\AccountController;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser;
use Oro\Component\PhpUtils\Attribute\Reader\AttributeReader;
use PHPUnit\Framework\TestCase;

class AttributeReaderTest extends TestCase
{
    public const REFLECTION_CLASS_NAME = AccountController::class;
    private AttributeReader $reader;

    public function setUp(): void
    {
        $this->reader = new AttributeReader();
    }

    public function testReadClassAclAttributes(): void
    {
        $reflection = new \ReflectionClass(self::REFLECTION_CLASS_NAME);
        $attributes = $this->reader->getClassAttributes($reflection);

        $this->assertCount(2, $attributes);
        $this->assertArrayHasKey(Acl::class, $attributes);

        /** @var Acl $attribute */
        $attribute = $attributes[Acl::class];

        $this->assertInstanceOf(Acl::class, $attribute);
        $this->assertEquals('oro_cms_user', $attribute->getId());
        $this->assertTrue($attribute->getIgnoreClassAcl());
        $this->assertEquals('entity', $attribute->getType());
        $this->assertEquals(CmsUser::class, $attribute->getClass());
        $this->assertEquals('ALL', $attribute->getPermission());
        $this->assertEquals('TEST_GROUP', $attribute->getGroup());
        $this->assertEquals('CmsUsers ACL', $attribute->getLabel());
        $this->assertEquals('CRUD for CmsUser ACL', $attribute->getDescription());
        $this->assertEquals('CRUD', $attribute->getCategory());
    }

    public function testReadMethodAclAttributes(): void
    {
        $reflection = new \ReflectionClass(self::REFLECTION_CLASS_NAME);
        $reflectionMethod = $reflection->getMethod('viewAction');
        $attributes = $this->reader->getMethodAttributes($reflectionMethod);

        $this->assertCount(4, $attributes);
        $this->assertArrayHasKey(Acl::class, $attributes);
        $this->assertArrayHasKey(AclAncestor::class, $attributes);

        /** @var Acl $aclAttribute */
        $aclAttribute = $attributes[Acl::class];
        $aclAncestorAttribute = $attributes[AclAncestor::class];

        $this->assertInstanceOf(Acl::class, $aclAttribute);
        $this->assertFalse($aclAttribute->getIgnoreClassAcl());
        $this->assertEquals('entity', $aclAttribute->getType());
        $this->assertEquals(CmsUser::class, $aclAttribute->getClass());
        $this->assertEquals('VIEW', $aclAttribute->getPermission());
        $this->assertEquals('TEST_GROUP', $aclAttribute->getGroup());
        $this->assertEquals('CmsUsers ACL View', $aclAttribute->getLabel());
        $this->assertEquals('View for CmsUser ACL', $aclAttribute->getDescription());
        $this->assertEquals('View', $aclAttribute->getCategory());

        $this->assertEquals('oro_cms_user_view_case', $aclAncestorAttribute->getId());
    }

    public function testReadMethodAclAttribute(): void
    {
        $reflection = new \ReflectionClass(self::REFLECTION_CLASS_NAME);
        $reflectionMethod = $reflection->getMethod('viewAction');
        $attribute = $this->reader->getMethodAttribute($reflectionMethod, AclAncestor::class);

        $this->assertEquals('oro_cms_user_view_case', $attribute->getId());
    }
}
