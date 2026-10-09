<?php

namespace App\Http\Requests\Admin;

use App\Concerns\TenantAdminValidationRules;
use App\Concerns\TenantValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    use TenantAdminValidationRules;
    use TenantValidationRules;

    protected function prepareForValidation(): void
    {
        $this->normalizarEntradaTenant();
    }

    /**
     * Datos del tenant más los de sus administradores (lista "admins", mínimo uno).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->tenantRules(),
            'admins' => ['required', 'array', 'min:1'],
            ...$this->reglasAdministrador('admins.*.'),
        ];
    }
}
