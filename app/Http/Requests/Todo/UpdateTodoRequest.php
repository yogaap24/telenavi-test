<?php

namespace App\Http\Requests\Todo;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTodoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'string',
            'assignee' => 'string',
            'due_date' => 'date|after_or_equal:today',
            'time_tracked' => 'numeric|min:0',
            'status' => 'string|in:pending,open,in_progress,completed',
            'priority' => 'string|in:low,medium,high',
        ];
    }
}