<?php
namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Entity\Repository;

use Oro\Bundle\EmbeddedFormBundle\Entity\Repository\EmbeddedFormRepository;

class EmbeddedFormRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructed()
    {
        $em = $this->getMock('\Doctrine\ORM\EntityManager', [], [], '', false);
        $classMetaData = $this->getMock('\Doctrine\ORM\Mapping\ClassMetadata', [], [], '', false);
        new EmbeddedFormRepository($em, $classMetaData);
    }
}
