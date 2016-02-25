<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\TestFrameworkBundle\Entity\TestDepartment;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee;

class LoadTableInheritanceData extends AbstractFixture implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $department = new TestDepartment();
        $department->setName('TestDepartment');
        $manager->persist($department);

        $employee = new TestEmployee();
        $employee->setName('TestEmployee');
        $employee->setDepartment($department);
        $employee->setPosition('developer');
        $manager->persist($employee);

        $manager->flush();

        $this->setReference('test_department', $department);
    }
}
