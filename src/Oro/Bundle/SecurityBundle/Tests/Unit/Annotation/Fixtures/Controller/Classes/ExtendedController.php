<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Fixtures\Controller\Classes;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Acl(
 *      id = "user_test_extended_controller",
 *      type="action",
 *      group_name="Test Group",
 *      label = "Extended test controller for ACL"
 * )
 */
class ExtendedController extends MainTestController
{
    /**
     * @return Response
     */
    public function test2Action()
    {
        return new Response('test');
    }

    /**
     * @AclAncestor("user_test_main_controller_action1")
     * @return Response
     */
    public function test3Action()
    {
        return new Response('test');
    }

    /**
     * @Acl(
     *      id = "user_test_main_controller_action4_rewrite",
     *      type="action",
     *      group_name="Another Group",
     *      label="Action 4 Rewrite"
     * )
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function test4Action()
    {
        return new Response('test');
    }

    /**
     * @Acl(
     *      id = "user_test_main_controller_action5",
     *      type="action",
     *      group_name="Another Group",
     *      label="Action 5"
     * )
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function test5Action()
    {
        return new Response('test');
    }
}
