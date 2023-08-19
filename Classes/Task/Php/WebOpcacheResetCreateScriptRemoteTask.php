<?php
namespace Mindscreen\Surf\Task\Php;

/*                                                                        *
 * This script belongs to the TYPO3 project "TYPO3 Surf"                  *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;

use TYPO3\Surf\Domain\Generator\RandomBytesGenerator;
use TYPO3\Surf\Domain\Generator\RandomBytesGeneratorInterface;

use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareInterface;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareTrait;
use TYPO3\Surf\Task\Php\WebOpcacheResetExecuteTask;
use TYPO3\Surf\Task\ShellTask;

/**
 * Create a script to reset the PHP opcache on the remote server
 *
 * The task creates a temporary script (in the shared folder of the remote server) for resetting the PHP opcache in a
 * later web request. A secondary task will execute an HTTP request and thus execute the script.
 *
 * The opcache reset has to be done in the webserver process, so a simple CLI command would not help.
 */
class WebOpcacheResetCreateScriptRemoteTask extends ShellTask implements ShellCommandServiceAwareInterface
{
    use ShellCommandServiceAwareTrait;

    private RandomBytesGeneratorInterface $randomBytesGenerator;

    /**
     * WebOpcacheResetCreateScriptTask constructor.
     *
     * @param RandomBytesGeneratorInterface|null $randomBytesGenerator
     */
    public function __construct(RandomBytesGeneratorInterface $randomBytesGenerator = null)
    {
        if ( ! $randomBytesGenerator instanceof RandomBytesGeneratorInterface) {
            $randomBytesGenerator = new RandomBytesGenerator();
        }

        $this->randomBytesGenerator = $randomBytesGenerator;
    }

    /**
     * Execute this task
     *
     * @param Node $node
     * @param Application $application
     * @param Deployment $deployment
     * @param array $options Supported options: "scriptBasePath" and "scriptIdentifier"
     * @return void
     * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
     * @throws \TYPO3\Surf\Exception\TaskExecutionException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()): void
    {
        if (!isset($options['scriptIdentifier'])) {
            // Generate random identifier
            $scriptIdentifier = bin2hex($this->randomBytesGenerator->generate(32));

            // Store the script identifier as an application option
            $application->setOption(WebOpcacheResetExecuteTask::class . '[scriptIdentifier]', $scriptIdentifier);
        } else {
            $scriptIdentifier = $options['scriptIdentifier'];
        }

        $options['command'] = 'mkdir -p {sharedPath}/opcache'
            . ' && cd {sharedPath}/opcache'
            . ' && rm -f surf-opcache-reset-*'
            . ' && cat > surf-opcache-reset-' . $scriptIdentifier . '.php << EOF
<?php
if (function_exists("opcache_reset")) {
    opcache_reset();
}
@unlink(__FILE__);
echo "success";
EOF';

        parent::execute($node, $application, $deployment, $options);
    }
}
