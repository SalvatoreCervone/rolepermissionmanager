<?php

namespace SalvatoreCervone\RolePermissionManager\Commands;

use Illuminate\Console\Command;
use SalvatoreCervone\RolePermissionManager\Http\Controllers\SimulatorController;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;

class CheckAclCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'acl:check
                            {user : User ID or Email}
                            {resource : Route identifier or custom resource name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test and diagnose authorization for a user against a route or resource';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userArg = $this->argument('user');
        $identifier = $this->argument('resource');

        $userModel = AclRegistry::getUserModelClass();

        $user = is_numeric($userArg)
            ? (new $userModel)->newQuery()->with(['roles.permissions', 'permissions'])->find($userArg)
            : (new $userModel)->newQuery()->with(['roles.permissions', 'permissions'])->where('email', $userArg)->first();

        if (!$user) {
            $this->error("User '{$userArg}' not found.");
            return self::FAILURE;
        }

        $resource = SecuredResource::findByIdentifier($identifier);

        $simulator = new SimulatorController();
        $evaluation = $simulator->evaluateAccess($user, $identifier, $resource);

        $this->newLine();
        $this->info("=== ACL Diagnostic Check ===");
        $this->line("User:       " . ($user->name ?? $user->email) . " (ID: {$user->id})");
        $this->line("Resource:   {$identifier}");
        $this->newLine();

        $rows = [];
        foreach ($evaluation['steps'] as $idx => $step) {
            $icon = match ($step['status']) {
                'success' => '✔ OK',
                'danger'  => '✘ FAIL',
                default   => 'ℹ INFO',
            };
            $rows[] = [$idx + 1, $step['name'], $icon, $step['details']];
        }

        $this->table(['#', 'Step', 'Status', 'Details'], $rows);

        $this->newLine();
        if ($evaluation['isAllowed']) {
            $this->info("🟢 VERDICT: AUTHORIZED (200 OK)");
            $this->line($evaluation['verdictReason']);
        } else {
            $this->error("🔴 VERDICT: FORBIDDEN (403 Access Denied)");
            $this->line($evaluation['verdictReason']);
        }
        $this->newLine();

        return self::SUCCESS;
    }
}
