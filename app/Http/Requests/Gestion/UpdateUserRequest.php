<?php

namespace App\Http\Requests\Gestion;

use App\Concerns\TenantUserValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    use TenantUserValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge(['activo' => $this->boolean('activo')]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->reglasUsuario(
            ignorarUsuarioId: $this->route('usuario')->id,
            exigirPassword: false,
        );
    }
}
