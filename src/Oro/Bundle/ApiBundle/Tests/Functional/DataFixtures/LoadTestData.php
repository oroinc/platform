<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\TestFrameworkBundle\Entity\TestDepartment;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee;

class LoadTestData extends AbstractFixture implements ContainerAwareInterface
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
        $departments = [];
        for ($i = 0; $i < 3; $i++) {
            $department = new TestDepartment();
            $department->setName('TestDepartment' . $i);

            $manager->persist($department);
            $departments[] = $department;

            $this->setReference('TestDepartment' . $i, $department);
        }

        for ($i = 1; $i <= 30; $i++) {
            switch ($i) {
                case $i > 20:
                    $department = $departments[2];
                    break;
                case $i > 10 && $i < 21:
                    $department = $departments[1];
                    break;
                case $i > 0 && $i < 11:
                default:
                    $department = $departments[0];
            }

            $employee = new TestEmployee();
            $employee->setName('TestEmployee' . $i);
            $employee->setDepartment($department);
            $employee->setPosition('developer');

            $manager->persist($employee);

            $this->setReference('TestEmployee' . $i, $employee);
        }

        $manager->flush();
    }
}
