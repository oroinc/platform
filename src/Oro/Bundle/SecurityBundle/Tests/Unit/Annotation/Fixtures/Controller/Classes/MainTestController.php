<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Fixtures\Controller\Classes;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Acl(
 *      id = "user_test_main_controller",
 *      type="action",
 *      group_name="Test Group",
 *      label = "Test controller for ACL"
 * )
 */
class MainTestController extends AbstractController
{
    /**
     * @Acl(
     *      id = "user_test_main_controller_action1",
     *      type="entity",
     *      class="AcmeBundle\Entity\SomeClass",
     *      permission="VIEW",
     *      group_name="Test Group",
     *      label="Action 1"
     * )
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function test1Action()
    {
        return new Response('test');
    }

    /**
     * @Acl(
     *      id = "user_test_main_controller_action2",
     *      type="action",
     *      group_name="Another Group",
     *      label="Action 2"
     * )
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function test2Action()
    {
        return new Response('test');
    }

    /**
     * @AclAncestor("user_test_main_controller_action2")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function test3Action()
    {
        return new Response('test');
    }

    /**
     * @Acl(
     *      id = "user_test_main_controller_action4",
     *      type="action",
     *      group_name="Another Group",
     *      label="Action 4"
     * )
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function test4Action()
    {
        return new Response('test');
    }

    public function testNoAclAction()
    {
        return new Response('test');
    }

    public function noActionMethod()
    {
        return array();
    }
}
