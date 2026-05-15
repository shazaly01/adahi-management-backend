<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'distribution_entity_id' => 'required|exists:distribution_entities,id',
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|string|email|max:255',
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name,guard_name,api',
        ];
    }

    public function attributes(): array
    {
        return [
            'distribution_entity_id' => 'جهة التوزيع',
            'full_name' => 'الاسم الكامل',
            'username' => 'اسم المستخدم',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'roles' => 'الأدوار',
        ];
    }
}
