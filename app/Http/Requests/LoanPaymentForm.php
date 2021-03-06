<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Loan;

class LoanPaymentForm extends FormRequest
{
    public function wantsJson()
    {
        return true;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!is_null($this->loan->disbursement_date) && $this->loan->role->name === 'PRE-cobranzas') return true;
        abort(403, 'El préstamo aún no ha sido desembolsado');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $latest_payment = $this->loan->last_payment;
        if ($latest_payment) {
            $date = $latest_payment->estimated_date;
        } else {
            $date = $this->loan->disbursement_date;
        } 
        $rules = [
            'procedure_modality_id' => ['integer', 'exists:procedure_modalities,id'],
            'affiliate_id' => ['integer', 'exists:affiliates,id'],
            'amortization_type_id' => ['integer', 'exists:amortization_types,id'],
            'paid_by' => ['string', 'in:T,G'],
            'voucher' => ['nullable','string','min:3'],
            'estimated_date' => 'nullable|date_format:Y-m-d|after_or_equal:'.$date,
            'estimated_quota' => 'nullable|numeric|min:1',
            'user_id' => ['nullable', 'integer', 'exists:users,id']
        ];
        switch ($this->method()) {
            case 'POST': {
                foreach (array_slice($rules, 0, 4) as $key => $rule) {
                    array_push($rules[$key], 'required');
                }
                return array_merge($rules, [
                    'liquidate' => 'nullable|boolean',
                    'description' => 'nullable|string|min:2',
                ]);
            }
            default: {
                return $rules;
            }
        }
        return $rules;
    }

    public function messages()
    {
        return [
            'estimated_date.after_or_equal' => 'La fecha estimada debe ser igual a hoy o posterior',
            'liquidate.boolean' => 'EL atributo liquidado debe ser (true or false)'
        ];
    }
}
