<?php

namespace App\Http\Requests\Admin;

use App\Concerns\TenantAdminValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTenantAdminRequest extends FormRequest
{
    use TenantAdminValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->reglasAdministrador(tenantId: $this->route('tenant')->id);
    }
}
