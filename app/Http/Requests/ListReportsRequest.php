<?php

namespace App\Http\Requests;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListReportsRequest extends FormRequest
{
    /**
     * Columns the report list may be sorted by.
     */
    public const SORTABLE = ['created_at', 'updated_at', 'assigned_at', 'resolved_at', 'status'];

    /**
     * Only operators and admins may list reports (ReportPolicy::viewAny).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Report::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(ReportStatus::class)],
            'category_id' => ['sometimes', 'integer', 'min:1'],
            // Either `me` (the authenticated user) or a numeric operator user id.
            'assigned_to' => ['sometimes', 'string', 'regex:/^(me|[1-9][0-9]*)$/'],
            'created_from' => ['sometimes', 'date_format:Y-m-d'],
            'created_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:created_from'],
            'sort' => ['sometimes', Rule::in(self::SORTABLE)],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.regex' => 'The assigned to field must be "me" or a user id.',
        ];
    }

    /**
     * Resolve `assigned_to` to a user id, mapping `me` to the authenticated user.
     */
    public function assignedOperatorId(): ?int
    {
        $assignedTo = $this->validated('assigned_to');

        if ($assignedTo === null) {
            return null;
        }

        return $assignedTo === 'me' ? $this->user()->id : (int) $assignedTo;
    }
}
