<?php

namespace App\Http\Requests;

use App\Models\Criterion;
use Illuminate\Foundation\Http\FormRequest;

class StoreScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $criterion = Criterion::findOrFail($this->input('criterion_id'));

        return [
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'contestant_id' => ['required', 'integer', 'exists:contestants,id'],
            'criterion_id' => ['required', 'integer', 'exists:criteria,id'],
            'score' => ['required', 'numeric', 'min:0', "max:{$criterion->max_score}"],
        ];
    }
}

