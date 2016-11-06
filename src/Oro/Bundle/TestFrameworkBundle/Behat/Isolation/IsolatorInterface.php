<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\DependencyInjection\ContainerInterface;

interface IsolatorInterface
{
    public function start();

    public function beforeTest();

    public function afterTest();

    public function terminate();

    /**
     * @param ContainerInterface $container
     * @return bool
     */
    public function isApplicable(ContainerInterface $container);
}
