<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    // Permission check at the request level — returns 403 before validation runs
    // if the user doesn't have 'add student note'.
    public function authorize(): bool
    {
        return $this->user()->can('add student note');
    }

    public function rules(): array
    {
        return [
            // student_ids is required and must have at least one entry — a note with
            // no students makes no sense.
            'student_ids'           => ['required', 'array', 'min:1'],
            'student_ids.*'         => ['integer', 'exists:students,id'],
            'class_id'              => ['required', 'integer', 'exists:classes,id'],
            'note_text'             => ['required', 'string', 'max:5000'],
            'note_date'             => ['required', 'date'],
            'confidentiality_level' => ['nullable', 'string', 'max:100'],
        ];
    }
}
