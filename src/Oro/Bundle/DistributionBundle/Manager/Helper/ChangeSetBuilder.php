<?php
namespace Oro\Bundle\DistributionBundle\Manager\Helper;

use Composer\Package\PackageInterface;

class ChangeSetBuilder
{
    /**
     * @param PackageInterface[] $previousInstalled
     * @param PackageInterface[] $currentlyInstalled
     * @return array - [$installed, $updated, $uninstalled]
     */
    public function build(array $previousInstalled, array $currentlyInstalled)
    {
        $allPackages = array_unique(array_merge($previousInstalled, $currentlyInstalled), SORT_REGULAR);

        $isExist = function (PackageInterface $p, array $arrayOfPackages, callable $equalsCallback) {
            foreach ($arrayOfPackages as $pi) {
                if ($equalsCallback($p, $pi)) {

                    return true;
                }
            }

            return false;
        };

        $equalsByName = function (PackageInterface $p1, PackageInterface $p2) {

            return $p1->getName() == $p2->getName();
        };

        $equalsBySourceReference = function (PackageInterface $p1, PackageInterface $p2) {

            return $p1->getSourceReference() == $p2->getSourceReference();
        };

        $justInstalled = [];
        $justUpdated = [];
        $justUninstalled = [];
        foreach ($allPackages as $p) {
            if ($isExist($p, $currentlyInstalled, $equalsByName)
                && !$isExist($p, $previousInstalled, $equalsByName)
            ) {

                $justInstalled[] = $p;

            } elseif (!$isExist($p, $currentlyInstalled, $equalsByName)
                && $isExist($p, $previousInstalled, $equalsByName)
            ) {

                $justUninstalled[] = $p;

            } elseif ($isExist($p, $currentlyInstalled, $equalsByName)
                && $isExist($p, $previousInstalled, $equalsByName)
                && $isExist($p, $currentlyInstalled, $equalsBySourceReference)
                && !$isExist($p, $previousInstalled, $equalsBySourceReference)
            ) {

                $justUpdated[] = $p;

            }
        }

        return [$justInstalled, $justUpdated, $justUninstalled];
    }
} 