<?php
namespace Mindscreen\Surf\Application;

/*                                                                        *
 * This script belongs to the TYPO3 project "TYPO3 Surf"                  *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Workflow;

/**
 * A base application with Git checkout and basic release directory structure
 *
 * Most specific applications will extend from BaseApplication.
 */
class Magento extends \TYPO3\Surf\Application\BaseApplication
{

    /**
     * @param \TYPO3\Surf\Domain\Model\Workflow $workflow
     * @param string $packageMethod
     * @return void
     */
    protected function registerTasksForPackageMethod(Workflow $workflow, $packageMethod)
    {
        switch ($packageMethod) {
            case 'git':
                $workflow->addTask('TYPO3\\Surf\\Task\\Package\\GitTask', 'package', $this);
                $workflow->defineTask(
                    'Mindscreen\\Surf\\DefinedTask\\Composer\\MagentoLocalInstallTask',
                    'Mindscreen\\Surf\\Task\\Composer\\MagentoInstallTask', array(
                        'nodeName' => 'localhost',
                        'useApplicationWorkspace' => true
                    )
                );
                $workflow->afterTask('TYPO3\\Surf\\Task\\Package\\GitTask', 'Mindscreen\\Surf\\DefinedTask\\Composer\\MagentoLocalInstallTask', $this);
                break;
        }
    }

}
