<?php
namespace Mindscreen\Surf\Application\TYPO3;

/*                                                                        *
 * This script belongs to the TYPO3 project "TYPO3 Surf"                  *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Application\BaseApplication;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Workflow;

/**
 * TYPO3 CMS application
 */
class CMS extends \TYPO3\Surf\Application\TYPO3\CMS
{

    /**
     * Basic application specific options
     * @var array
     */
    protected $options = array(
        'packageMethod' => 'git',
        'transferMethod' => 'rsync',
        'rsyncFlags' => '--recursive --times --perms --links --delete',
        'keepReleases' => 3,
        'composerCommandPath' => '/usr/local/bin/composer',
    );

    /**
     * @var bool
     */
    protected $opcacheResetEnabled = true;

    /**
     * @var string
     */
    protected $baseUrl = '';

    /**
     * Register tasks for this application
     *
     * @param Workflow $workflow
     * @param Deployment $deployment
     * @return void
     */
    public function registerTasks(Workflow $workflow, Deployment $deployment): void
    {
        parent::registerTasks($workflow, $deployment);

        $symlinks = [
            ($this->getOption('webDirectory') ?: 'web') . '/typo3conf/LocalConfiguration.php' => '../../../../shared/Configuration/typo3conf/LocalConfiguration.php'
        ];

        if ($this->isOpcacheResetEnabled()) {
            $workflow->setTaskOptions(\TYPO3\Surf\Task\Php\WebOpcacheResetExecuteTask::class, [
                'baseUrl' => $this->getBaseUrl() . '/opcache',
            ]);

            $symlinks[($this->getOption('webDirectory') ?: 'web') . '/opcache'] = '../../../shared/opcache';

            $workflow
                ->beforeStage('transfer', \Mindscreen\Surf\Task\Php\WebOpcacheResetCreateScriptRemoteTask::class)
                ->afterStage('switch', \TYPO3\Surf\Task\Php\WebOpcacheResetExecuteTask::class);
        }

        $workflow->setTaskOptions(\TYPO3\Surf\Task\Generic\CreateSymlinksTask::class, [
            'symlinks' => $symlinks
        ]);
    }

    /**
     * @return bool
     */
    public function isOpcacheResetEnabled()
    {
        return $this->opcacheResetEnabled;
    }

    /**
     * @param bool $opcacheResetEnabled
     */
    public function setOpcacheResetEnabled($opcacheResetEnabled)
    {
        $this->opcacheResetEnabled = $opcacheResetEnabled;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }
}
