<?php
namespace Mindscreen\Surf\Application\Neos;

/*                                                                        *
 * This script belongs to the TYPO3 project "TYPO3 Surf"                  *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Workflow;
use TYPO3\Surf\Task\Neos\Flow\FlushCacheListTask;

/**
 * A Neos website template
 */
class Neos extends Flow
{
    /**
     * Constructor
     * @param string $name
     */
    public function __construct(string $name = 'Neos CMS')
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

        $definedTasks = $workflow->getTasks()['defined'];
        if (!isset($definedTasks[FlushCacheListTask::class])) {
            if ($this->getVersion() < '4.0') {
                $flushCacheList = 'TYPO3_TypoScript_Content, Flow_Mvc_Routing_Resolve, Flow_Mvc_Routing_Route';
            } else {
                $flushCacheList = 'Neos_Fusion_Content, Flow_Mvc_Routing_Resolve, Flow_Mvc_Routing_Route';
            }
            $workflow->setTaskOptions(FlushCacheListTask::class, [
                'flushCacheList' => $flushCacheList
            ]);
        }

        $workflow->afterStage('switch', FlushCacheListTask::class);
    }

}
