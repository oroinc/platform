<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\EntityStub;

class EntitySerializePhp7Test extends \PHPUnit_Framework_TestCase
{
    /**
     * Test if the bug https://bugs.php.net/bug.php?id=71940 exist
     */
    public function testCheckSerializationOnPhp7()
    {
        $this->markTestSkipped('Bug was fixed in php 7.0.6');

        if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
            $didErrorFired = false;
            $identity = new RoleSecurityIdentity('test-role-identity');

            $entity1 = new EntityStub($identity);
            $entity2 = new EntityStub($identity);

            $serialize = serialize([$entity1, $entity2]);

            try {
                unserialize($serialize);
            } catch (\Exception $e) {
                if ($e->getMessage() === EntityStub::getExceptionMesage()) {
                    $didErrorFired = true;
                }
            }

            $this->assertTrue(
                $didErrorFired,
                'The bug (https://bugs.php.net/bug.php?id=71940) ' .
                'with serialization was fixed on version ' .
                PHP_VERSION
            );
        } else {
            $this->markTestSkipped(
                'This test implemented to test serialization only on php7'
            );
        }
    }
}
