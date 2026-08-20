<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Controllers;

use Illuminate\Http\Request;
use SalvatoreCervone\RolePermissionManager\Models\ScannerRule;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;

class ScannerRuleController
{
    /**
     * Display a listing of custom and default scanner rules.
     */
    public function index(Request $request)
    {
        $ruleModel = config('rolepermissionmanager.models.scanner_rule', ScannerRule::class);
        $rules = $ruleModel::orderBy('type')->orderBy('created_at', 'desc')->get();

        $configExcludedPrefixes = config('rolepermissionmanager.scanner.excluded_prefixes', []);
        $configExcludedNames = config('rolepermissionmanager.scanner.excluded_names', []);

        return view('acl::scanner_rules.index', compact('rules', 'configExcludedPrefixes', 'configExcludedNames'));
    }

    /**
     * Store a newly created scanner rule.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'        => 'required|in:exclude,include',
            'target'      => 'required|in:name,prefix',
            'pattern'     => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $ruleModel = config('rolepermissionmanager.models.scanner_rule', ScannerRule::class);
        $rule = $ruleModel::create([
            'type'        => $validated['type'],
            'target'      => $validated['target'],
            'pattern'     => trim($validated['pattern']),
            'description' => $validated['description'] ?? null,
            'is_active'   => true,
        ]);

        AclRegistry::refreshCache();

        return redirect()
            ->route('acl.scanner_rules.index')
            ->with('success', __('acl::scanner.rule_created', ['pattern' => $rule->pattern]));
    }

    /**
     * Toggle the active status of a scanner rule.
     */
    public function toggle($id)
    {
        $ruleModel = config('rolepermissionmanager.models.scanner_rule', ScannerRule::class);
        $rule = $ruleModel::findOrFail($id);

        $rule->update([
            'is_active' => !$rule->is_active,
        ]);

        AclRegistry::refreshCache();

        return redirect()
            ->route('acl.scanner_rules.index')
            ->with('success', __('acl::scanner.rule_toggled', ['pattern' => $rule->pattern]));
    }

    /**
     * Remove the specified scanner rule from storage.
     */
    public function destroy($id)
    {
        $ruleModel = config('rolepermissionmanager.models.scanner_rule', ScannerRule::class);
        $rule = $ruleModel::findOrFail($id);
        $pattern = $rule->pattern;

        $rule->delete();
        AclRegistry::refreshCache();

        return redirect()
            ->route('acl.scanner_rules.index')
            ->with('success', __('acl::scanner.rule_deleted', ['pattern' => $pattern]));
    }
}
