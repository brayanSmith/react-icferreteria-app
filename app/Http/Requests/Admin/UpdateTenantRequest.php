<?php

namespace App\Http\Requests\Admin;

use App\Concerns\TenantValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    use TenantValidationRules;

    protected function prepareForValidation(): void
    {
        $this->normalizarEntradaTenant();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->tenantRules($this->route('tenant'));
    }
}
