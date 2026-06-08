<?php

namespace Workdo\Workflow\Services;

use Workdo\Workflow\Models\Workflow;
use Workdo\Workflow\Models\WorkflowLog;

class WorkflowService
{
    public function trigger(string $module, string $triggerType, array $data = [])
    {
        $workflows = Workflow::with('actions')
            ->where('is_active', true)
            ->where('trigger_module', $module)
            ->where('trigger_type', $triggerType)
            ->get();

        foreach ($workflows as $workflow) {
            $this->execute($workflow, $data);
        }
    }

    public function execute(Workflow $workflow, array $triggerData = [])
    {
        foreach ($workflow->actions as $action) {
            try {
                WorkflowLog::create([
                    'workflow_id' => $workflow->id,
                    'trigger_data' => $triggerData,
                    'action_data' => $action->config ?? [],
                    'status' => 'completed',
                    'executed_at' => now(),
                ]);
            } catch (\Exception $e) {
                WorkflowLog::create([
                    'workflow_id' => $workflow->id,
                    'trigger_data' => $triggerData,
                    'action_data' => $action->config ?? [],
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'executed_at' => now(),
                ]);
            }
        }
    }
}
