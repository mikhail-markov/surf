<?php
namespace Mindscreen\Surf\Task\Gitlab;

/*                                                                        *
 * This script belongs to the TYPO3 project "TYPO3 Surf"                  *
 *                                                                        *
 *                                                                        */

use Gitlab\Client;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use Gitlab\Api\MergeRequests;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareInterface;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareTrait;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * A task which can be used to tag a git repository and its submodules
 *
 */
class ApplyOpenMergeRequestsTask extends Task implements ShellCommandServiceAwareInterface
{
    use ShellCommandServiceAwareTrait;

    /**
     * Execute this task
     *
     * Options:
     *   gitlabApiBaseUrl: the Gitlab API URL
     *   gitlabApiToken: The token to access the Gitlab API
     *   gitlabProjectId: The Gitlab project ID
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
        $requiredOptions = ['gitlabApiBaseUrl', 'gitlabProjectId'];
        foreach ($requiredOptions as $optionName) {
            if (!isset($options[$optionName]) || !$options[$optionName]) {
                throw new InvalidConfigurationException('Option ' . $optionName . ' not set.', 1511979114);
            }
        }

        $gitlabApiToken = $this->getGitlabApiToken($application, $deployment, $options['gitlabApiBaseUrl']);
        $client = new Client();
        $client->setUrl($options['gitlabApiBaseUrl']);
        $client->authenticate($gitlabApiToken, Client::AUTH_HTTP_TOKEN);

        $workingDirectory = $deployment->getWorkspacePath($application);
        $node = $deployment->getNode('localhost');
        $baseBranch = $options['branch'] ?? 'master';
        $branchName = 'integration_' . uniqid();
        $command = strtr("
                            cd $workingDirectory
                            && git checkout -b $branchName
                        ", "\t\n", '  ');
        $this->shell->executeOrSimulate($command, $node, $deployment);

        $openMergeRequests = $client->mergeRequests()->all($options['gitlabProjectId'], ['state' => MergeRequests::STATE_OPENED]);
        foreach ($openMergeRequests as $mergeRequest) {
            if (
                $mergeRequest['target_branch'] == $baseBranch
                && $mergeRequest['source_project_id'] == $options['gitlabProjectId']
                && $mergeRequest['target_project_id'] == $options['gitlabProjectId']
            ) {
                $branchToMerge = escapeshellarg('origin/' . $mergeRequest['source_branch']);
                $command = strtr("
                            cd $workingDirectory
                            && git merge $branchToMerge --no-ff  -m \"auto merge of branch: $branchToMerge\"
                        ", "\t\n", '  ');
                $this->shell->executeOrSimulate($command, $node, $deployment);
            }
        }
    }

    /**
     * @param Application $application
     * @param Deployment $deployment
     * @param string $apiBaseUrl
     * @return string
     * @throws InvalidConfigurationException
     */
    protected function getGitlabApiToken(Application $application, Deployment $deployment, string $apiBaseUrl): string
    {
        $gitlabHost = parse_url($apiBaseUrl, PHP_URL_HOST);
        $fileName = $deployment->getWorkspacePath($application) . '/../../../GITLAB_API_TOKEN';
        if (!file_exists($fileName)) {
            throw new InvalidConfigurationException('Please create file ' . $fileName . ' with your Gitlab API Token.', 1511979116);
        }

        $data = file_get_contents($fileName);
        $lines = preg_split("/\n/", $data);
        foreach ($lines as $line) {
            if (preg_match('/(.+)\s+(.+)/', $line, $matches)) {
                $host = $matches[1];
                $apiToken = $matches[2];
                if ($host === $gitlabHost) {
                    return trim($apiToken);
                }
            }
        }
        throw new InvalidConfigurationException('No api key found in GITLAB_API_TOKEN file for host ' . $gitlabHost . '.', 1511979117);
    }
}
