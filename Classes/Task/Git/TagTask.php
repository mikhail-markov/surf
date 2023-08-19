<?php
namespace Mindscreen\Surf\Task\Git;

/*                                                                        *
 * This script belongs to the TYPO3 project "TYPO3 Surf"                  *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * A task which can be used to tag a git repository and its submodules
 *
 */
class TagTask extends \TYPO3\Surf\Task\Git\TagTask
{

    /**
     * Execute this task
     *
     * Options:
     *   tagName: The tag name to use
     *   description: The description for the tag
     *   recurseIntoSubmodules: If true, tag submodules as well (optional)
     *   submoduleTagNamePrefix: Prefix for the submodule tags (optional)
     *
     * @param Node $node
     * @param Application $application
     * @param Deployment $deployment
     * @param array $options
     * @return void
     * @throws InvalidConfigurationException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = []): void
    {
        if ($application->getOption('transferMethod') === 'git') {
            parent::execute($node, $application, $deployment, $options);
        } else {
            $this->validateOptions($options);
            $options = $this->processOptions($options, $deployment);

            $localhost = new Node('localhost');
            $localhost->setHostname('localhost');

            $targetPath = $deployment->getWorkspacePath($application);
            $this->shell->executeOrSimulate(sprintf('cd ' . $targetPath . '; git tag -f -a -m %s %s', escapeshellarg($options['description']), escapeshellarg($options['tagName'])), $localhost, $deployment);
            if (isset($options['recurseIntoSubmodules']) && $options['recurseIntoSubmodules'] === true) {
                $submoduleCommand = escapeshellarg(sprintf('git tag -f -a -m %s %s', escapeshellarg($options['description']), escapeshellarg($options['submoduleTagNamePrefix'] . $options['tagName'])));
                $this->shell->executeOrSimulate(sprintf('cd ' . $targetPath . '; git submodule foreach %s', $submoduleCommand), $localhost, $deployment);
            }

            if (isset($options['pushTag']) && $options['pushTag'] === true) {
                $this->shell->executeOrSimulate(sprintf('cd ' . $targetPath . '; git push %s %s', escapeshellarg($options['remote']), escapeshellarg($options['tagName'])), $localhost, $deployment);
                if (isset($options['recurseIntoSubmodules']) && $options['recurseIntoSubmodules'] === true) {
                    $submoduleCommand = escapeshellarg(sprintf('git push %s %s', escapeshellarg($options['remote']), escapeshellarg($options['submoduleTagNamePrefix'] . $options['tagName'])));
                    $this->shell->executeOrSimulate(sprintf('cd ' . $targetPath . '; git submodule foreach %s', $submoduleCommand), $localhost, $deployment);
                }
            }
        }
    }
}
