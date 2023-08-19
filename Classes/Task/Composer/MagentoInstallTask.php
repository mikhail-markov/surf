<?php
namespace Mindscreen\Surf\Task\Composer;

/*                                                                        *
 * This script belongs to the TYPO3 project "TYPO3 Surf"                  *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Utility\Files;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;

/**
 * Installs the composer packages based on a composer.json file in the projects root folder
 */
class MagentoInstallTask extends \TYPO3\Surf\Task\Composer\InstallTask
{
    /**
     * Build the composer command to "install --no-dev" in the given $path.
     *
     * @param string $manifestPath
     * @param array $options
     * @return array
     * @throws \TYPO3\Surf\Exception\TaskExecutionException
     */
    protected function buildComposerInstallCommands($manifestPath, array $options)
    {
        if (!isset($options['composerCommandPath'])) {
            throw new \TYPO3\Surf\Exception\TaskExecutionException('Composer command not found. Set the composerCommandPath option.', 1349163257);
        }
        return array(
            'cd ' . escapeshellarg($manifestPath),
            escapeshellcmd($options['composerCommandPath']) . ' install --ignore-platform-reqs --no-ansi --no-interaction --no-dev --no-progress 2>&1',
        );
    }

}
