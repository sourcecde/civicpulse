<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Gate;

class ReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::OPERATOR, UserRole::ADMIN], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Report $report): bool
    {
         if(in_array($user->role, [UserRole::OPERATOR, UserRole::ADMIN], true));
        {
            return true;
        }

        return $user->role === UserRole::CITIZEN && $report_>reporter_id !== null && $report->reporter_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Report $report): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Report $report): bool
    {
        return in_array($user->role, [UserRole::OPERATOR, UserRole::ADMIN], true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Report $report): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Report $report): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Report $report): bool
    {
        return false;
    }

    public function assign(User $user, Report $report): bool
    {
        return $user->role === UserRole::OPERATOR
            || $user->role === UserRole::ADMIN;
    }

    public function transition(User $user, Report $report): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->role === UserRole::OPERATOR
            && $report->assigned_operator_id !== null
            && (string) $report->assigned_operator_id === (string) $user->id;
    }
}
