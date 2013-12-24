<?php
namespace Oro\Bundle\DistributionBundle\Manager\Helper;

use Composer\Package\PackageInterface;

class ChangeSetBuilder
{
    /**
     * @param PackageInterface[] $previousInstalled
     * @param PackageInterface[] $currentlyInstalled
     *
     * @return array - [$installed, $updated, $uninstalled]
     */
    public function build(array $previousInstalled, array $currentlyInstalled)
    {
        $allPackages = array_unique(array_merge($previousInstalled, $currentlyInstalled), SORT_REGULAR);

        $justInstalled = [];
        $justUpdated = [];
        $justUninstalled = [];
        foreach ($allPackages as $package) {
            if ($this->inArray($package, $currentlyInstalled, [$this, 'equalsByName'])
                && !$this->inArray($package, $previousInstalled, [$this, 'equalsByName'])
            ) {

                $justInstalled[] = $package;

            } elseif (!$this->inArray($package, $currentlyInstalled, [$this, 'equalsByName'])
                && $this->inArray($package, $previousInstalled, [$this, 'equalsByName'])
            ) {

                $justUninstalled[] = $package;

            } elseif ($this->inArray($package, $currentlyInstalled, [$this, 'equalsByName'])
                && $this->inArray($package, $previousInstalled, [$this, 'equalsByName'])
                && $this->inArray($package, $currentlyInstalled, [$this, 'equalsBySourceReference'])
                && !$this->inArray($package, $previousInstalled, [$this, 'equalsBySourceReference'])
            ) {

                $justUpdated[] = $package;

            }
        }

        return [$justInstalled, $justUpdated, $justUninstalled];
    }

    /**
     * @param PackageInterface $needle
     * @param array $haystack
     * @param callable $equalsCallback
     *
     * @return bool
     */
    protected function inArray(PackageInterface $needle, array $haystack, callable $equalsCallback)
    {
        foreach ($haystack as $package) {
            if ($equalsCallback($needle, $package)) {

                return true;
            }
        }

        return false;
    }

    /**
     * @param PackageInterface $package1
     * @param PackageInterface $package2
     *
     * @return bool
     */
    protected function equalsByName(PackageInterface $package1, PackageInterface $package2)
    {

        return $package1->getName() == $package2->getName();
    }

    /**
     * @param PackageInterface $package1
     * @param PackageInterface $package2
     *
     * @return bool
     */
    protected function equalsBySourceReference(PackageInterface $package1, PackageInterface $package2)
    {

        return $package1->getSourceReference() == $package2->getSourceReference();
    }
} 