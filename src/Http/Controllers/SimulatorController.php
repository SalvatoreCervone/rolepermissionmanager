<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;

class SimulatorController extends Controller
{
    /**
     * Get the configured user model class.
     */
    protected function getUserModelClass(): string
    {
        return AclRegistry::getUserModelClass();
    }

    /**
     * Display access simulator form & results.
     */
    public function index(Request $request)
    {
        $allModels = AclRegistry::getUserModelsConfig();
        $modelKey = $request->get('model', array_key_first($allModels));
        $modelConfig = AclRegistry::getUserModelConfig($modelKey);

        $userModel = $modelConfig['model'];
        $displayField = $modelConfig['display_field'] ?? 'name';
        $secondaryField = $modelConfig['secondary_field'] ?? 'email';

        $users = (new $userModel)->newQuery()->orderBy('id')->limit(50)->get();
        $resources = SecuredResource::active()->orderBy('identifier')->get();

        $selectedUserId = $request->get('user_id');
        $selectedIdentifier = $request->get('identifier');

        $evaluation = null;

        if ($selectedUserId && $selectedIdentifier) {
            $user = (new $userModel)->newQuery()->find($selectedUserId);
            $resource = SecuredResource::findByIdentifier($selectedIdentifier);

            if ($user) {
                $evaluation = $this->evaluateAccess($user, $selectedIdentifier, $resource);
            }
        }

        return view('acl::simulator.index', compact(
            'users',
            'resources',
            'selectedUserId',
            'selectedIdentifier',
            'evaluation',
            'displayField',
            'secondaryField',
            'allModels',
            'modelKey',
            'modelConfig'
        ));
    }

    /**
     * Evaluate detailed step-by-step authorization.
     */
    public function evaluateAccess($user, string $identifier, ?SecuredResource $resource): array
    {
        $steps = [];
        $isAllowed = false;
        $verdictReason = '';

        $superAdminRole = AclRegistry::getSuperAdminRole() ?? 'super-admin';
        $isSuperAdmin = AclRegistry::isSuperAdmin($user);
        $superAdminAllAccess = AclRegistry::superAdminHasAllAccess();

        $userRoles = method_exists($user, 'roles') ? $user->roles()->get() : collect();
        $allUserPerms = AclRegistry::getUserPermissions($user);
        $directPerms = method_exists($user, 'permissions') ? $user->permissions()->pluck('slug')->all() : [];
        $rolePerms = $userRoles->pluck('permissions')->flatten()->pluck('slug')->all();

        // Step 1: Unregistered resource
        if (!$resource) {
            $unprotectedBehavior = config('rolepermissionmanager.middleware.unprotected_behavior', 'allow');
            $isAllowed = ($unprotectedBehavior === 'allow');
            $steps[] = [
                'name'    => 'Registrazione Risorsa',
                'status'  => 'info',
                'details' => "La risorsa '{$identifier}' non è registrata nel database ACL. Comportamento globale: {$unprotectedBehavior}.",
            ];
            $verdictReason = $isAllowed ? 'Consentito perché la risorsa non è gestita da ACL (modalità allow).' : 'Bloccato perché la risorsa non è registrata nell\'ACL.';
            return compact('isAllowed', 'verdictReason', 'steps', 'userRoles', 'directPerms', 'rolePerms', 'allUserPerms', 'isSuperAdmin');
        }

        // Step 2: Public check
        if ($resource->is_public) {
            $steps[] = [
                'name'    => 'Controllo Accesso Pubblico',
                'status'  => 'success',
                'details' => 'La risorsa è contrassegnata come PUBBLICA. Accesso consentito a chiunque.',
            ];
            $isAllowed = true;
            $verdictReason = 'Accesso Consentito (Risorsa Pubblica).';
            return compact('isAllowed', 'verdictReason', 'steps', 'resource', 'userRoles', 'directPerms', 'rolePerms', 'allUserPerms', 'isSuperAdmin');
        }

        // Step 3: Super Admin check
        if ($isSuperAdmin && $superAdminAllAccess) {
            $steps[] = [
                'name'    => 'Bypass Super Admin',
                'status'  => 'success',
                'details' => "L'utente possiede il ruolo Super Admin ('{$superAdminRole}') con accesso globale abilitato. Tutti i controlli sono superati.",
            ];
            $isAllowed = true;
            $verdictReason = 'Accesso Consentito tramite Bypass Super Admin.';
            return compact('isAllowed', 'verdictReason', 'steps', 'resource', 'userRoles', 'directPerms', 'rolePerms', 'allUserPerms', 'isSuperAdmin');
        }

        // Step 4: Super Admin Only check
        if ($resource->is_super_admin_only) {
            if ($isSuperAdmin) {
                $steps[] = [
                    'name'    => 'Controllo Esclusiva Super Admin',
                    'status'  => 'success',
                    'details' => "La risorsa è riservata ESCLUSIVAMENTE al Super Admin. L'utente possiede il ruolo Super Admin.",
                ];
                $isAllowed = true;
                $verdictReason = 'Accesso Consentito: Risorsa riservata al Super Admin.';
                return compact('isAllowed', 'verdictReason', 'steps', 'resource', 'userRoles', 'directPerms', 'rolePerms', 'allUserPerms', 'isSuperAdmin');
            }

            $steps[] = [
                'name'    => 'Controllo Esclusiva Super Admin',
                'status'  => 'danger',
                'details' => "Questa risorsa è riservata ESCLUSIVAMENTE al Super Admin. L'utente non è Super Admin.",
            ];
            $isAllowed = false;
            $verdictReason = 'Accesso Negato: Risorsa riservata esclusivamente al Super Admin.';
            return compact('isAllowed', 'verdictReason', 'steps', 'resource', 'userRoles', 'directPerms', 'rolePerms', 'allUserPerms', 'isSuperAdmin');
        }

        // Step 5: Required Permissions
        $requiredPerms = $resource->permissions->pluck('slug')->all();
        $operator = $resource->operator ?? 'OR';

        if (empty($requiredPerms)) {
            $unassignedBehavior = config('rolepermissionmanager.middleware.unassigned_permissions_behavior', 'allow');
            $isAllowed = ($unassignedBehavior === 'allow');
            $steps[] = [
                'name'    => 'Nessun Permesso Assegnato',
                'status'  => $isAllowed ? 'success' : 'danger',
                'details' => "La risorsa è protetta ma non ha permessi collegati. Regola per non assegnati: {$unassignedBehavior}.",
            ];
            $verdictReason = $isAllowed ? 'Consentito ad ogni utente autenticato (0 permessi specificati).' : 'Negato (riservato solo ad admin per comportamento unassigned=deny).';
            return compact('isAllowed', 'verdictReason', 'steps', 'resource', 'userRoles', 'directPerms', 'rolePerms', 'allUserPerms', 'isSuperAdmin');
        }

        $matchedPerms = array_intersect($requiredPerms, $allUserPerms);
        $missingPerms = array_diff($requiredPerms, $allUserPerms);

        if ($operator === 'AND') {
            $isAllowed = empty($missingPerms);
            $steps[] = [
                'name'    => 'Valutazione Permessi (Operatore AND)',
                'status'  => $isAllowed ? 'success' : 'danger',
                'details' => $isAllowed
                    ? 'Tutti i permessi richiesti sono posseduti dall\'utente (' . implode(', ', $requiredPerms) . ').'
                    : 'Permessi mancanti richiesti per la regola AND: ' . implode(', ', $missingPerms),
            ];
        } else {
            $isAllowed = !empty($matchedPerms);
            $steps[] = [
                'name'    => 'Valutazione Permessi (Operatore OR)',
                'status'  => $isAllowed ? 'success' : 'danger',
                'details' => $isAllowed
                    ? 'Almeno un permesso richiesto è posseduto dall\'utente: ' . implode(', ', $matchedPerms)
                    : 'Nessun permesso richiesto corrisponde a quelli dell\'utente. Richiesti: ' . implode(', ', $requiredPerms),
            ];
        }

        $verdictReason = $isAllowed ? 'Accesso Consentito (permessi verificati con successo).' : 'Accesso Negato: Permessi insufficienti (403 Forbidden).';

        return compact(
            'isAllowed',
            'verdictReason',
            'steps',
            'resource',
            'requiredPerms',
            'operator',
            'matchedPerms',
            'missingPerms',
            'userRoles',
            'directPerms',
            'rolePerms',
            'allUserPerms',
            'isSuperAdmin'
        );
    }
}
