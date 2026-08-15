<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'script_body' => ['nullable', 'string'],
            'media_links' => ['nullable', 'array'],
            'media_links.*' => ['nullable', 'url', 'max:1000'],
            'media_files' => ['nullable', 'array', 'max:20'],
            'media_files.*' => ['file', 'mimes:pdf,xls,xlsx,jpg,jpeg,mp4,mov,webm,avi,mkv', 'max:26624'],
            'existing_media_files' => ['nullable', 'string', 'max:50000'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['required', 'in:draft,scheduled,posted,skipped'],
            'platform_ids' => ['nullable', 'array'],
            'platform_ids.*' => ['integer', 'exists:social_accounts,id'],
        ];
    }
}
