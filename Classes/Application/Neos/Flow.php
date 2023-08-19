<?php
namespace Mindscreen\Surf\Application\Neos;

/*                                                                        *
 * This script belongs to the TYPO3 project "TYPO3 Surf"                  *
 *                                                                        *
 *                                                                        */

use Mindscreen\Surf\Task\Php\WebOpcacheResetCreateScriptRemoteTask;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Workflow;
use TYPO3\Surf\Task\Generic\CreateSymlinksTask;
use TYPO3\Surf\Task\Php\WebOpcacheResetExecuteTask;

/**
 * A Neos website template
 */
class Flow extends \TYPO3\Surf\Application\Neos\Flow
{
    /**
     * Basic application specific options
     * @var array
     */
    protected array $options = array(
        'packageMethod' => 'git',
        'transferMethod' => 'rsync',
        'rsyncFlags' => '--recursive --times --perms --links --delete',
        'keepReleases' => 3,
        'composerCommandPath' => '/usr/local/bin/composer',
        'webDirectory' => self::DEFAULT_WEB_DIRECTORY,
    );

    /**
     * @var bool
     */
    protected bool $opcacheResetEnabled = true;

    /**
     * @var string
     */
    protected string $baseUrl = '';

    /**
     * Constructor
     * @param string $name
     */
    public function __construct(string $name = 'Neos Flow')
    {
        parent::__construct($name);
    }

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

        if ($this->isOpcacheResetEnabled()) {
            $workflow->setTaskOptions(WebOpcacheResetExecuteTask::class, [
                'baseUrl' => $this->getBaseUrl() . '/opcache',
            ]);
            $this->addSymlink('Web/opcache', '../../../shared/opcache');
            $this->setOption(CreateSymlinksTask::class . '[symlinks]', $this->getSymlinks());

            $workflow
                ->beforeStage('transfer', WebOpcacheResetCreateScriptRemoteTask::class)
                ->afterStage('switch', WebOpcacheResetExecuteTask::class);
        }
    }

    public function isOpcacheResetEnabled(): bool
    {
        return $this->opcacheResetEnabled;
    }

    public function setOpcacheResetEnabled(bool $opcacheResetEnabled)
    {
        $this->opcacheResetEnabled = $opcacheResetEnabled;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }


}
