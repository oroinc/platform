<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Controller;

use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CRUD for cms_users.
 */
#[Acl(
    id: "oro_cms_user",
    type: "entity",
    ignoreClassAcl: true,
    class: CmsUser::class,
    permission: "ALL",
    groupName: "TEST_GROUP",
    label: "CmsUsers ACL",
    description: "CRUD for CmsUser ACL",
    category: "CRUD",
)]
#[Route([
    "/oro_cms_user",
    "name" => "oro_cms_user"
])]
class AccountController extends AbstractController
{
    #[Acl(
        id: "oro_cms_user_view",
        type: "entity",
        ignoreClassAcl: false,
        class: CmsUser::class,
        permission: "VIEW",
        groupName: "TEST_GROUP",
        label: "CmsUsers ACL View",
        description: "View for CmsUser ACL",
        category: "View",
    )]
    #[AclAncestor(
        id: "oro_cms_user_view_case",
    )]
    #[Route([
        "/view/{id}",
        "name" => "oro_cms_user_view",
        "requirements" => ["id" => "\d+"]
    ])]
    #[Template('@OroSecurity/Account/view.html.twig')]
    public function viewAction(CmsUser $cmsUser): array
    {
        return [];
    }
}
