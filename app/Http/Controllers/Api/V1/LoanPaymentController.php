<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;
use App\LoanPayment;
use App\Voucher;
use App\LoanState;
use App\Http\Requests\LoanPaymentForm;
use App\Http\Requests\VoucherForm;
use App\Events\LoanFlowEvent;
use Carbon;
use DB;
use App\Helpers\Util;
use App\Http\Controllers\Api\V1\LoanController;

/** @group Cobranzas
* Datos de los trámites de Cobranzas
*/
class LoanPaymentController extends Controller
{
    /**
    * Editar Registro de pago
    * Edita el Registro de Pago realizado.
    * @urlParam loan required ID del prestamo. Example: 2
    * @urlParam loan_payment required ID del pago realizado. Example: 15
	* @bodyParam description string Texto de descripción. Example: Penalizacion regularizada
    * @authenticated
    * @responseFile responses/loan_payment/update_payment.200.json
    */
    public function update(Request $request, LoanPayment $loanPayment)
    {
        DB::beginTransaction();
        try {
            $payment = $loanPayment;
            $payment->description = $request->input('description');
            Util::save_record($loanPayment, 'datos-de-un-registro-pago', Util::concat_action($loanPayment));
            $loanPayment->update($payment->toArray());
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return $e;
        }
        return $payment;
    }

    /**
    * Anular Registro de Pago
    * @urlParam loan_payment required ID del pago. Example: 1
    * @authenticated
    * @responseFile responses/loan_payment/destroy_payment.200.json
    */
    public function destroy(LoanPayment $loanPayment)
    {
        DB::beginTransaction();
        try {
            $loanPayment->delete();
            Util::save_record($loanPayment, 'datos-de-un-registro-pago', 'eliminó registro pago: ' . $loanPayment->code);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return $e;
        }
        return $loanPayment;
    }

    /**
    * Nuevo pago por Tesoreria
    * Insertar registro de pago (loan_payment).
    * @urlParam loan_payment required ID del registro de pago. Example: 2
    * @bodyParam payment_type_id integer required ID de tipo de pago. Example: 1
    * @bodyParam voucher_type_id integer required ID de tipo de voucher. Example: 1
    * @bodyParam voucher_number integer número de voucher. Example: 12354121
    * @bodyParam description string Texto de descripción. Example: Penalizacion regularizada
    * @authenticated
    * @responseFile responses/loan_payment/set_voucher.200.json
    */
    public function set_voucher(VoucherForm $request, LoanPayment $loanPayment)
    {
        $Pagado = LoanState::whereName('Pagado')->first()->id;
        $PendientePago = LoanState::whereName('Pendiente de Pago')->first()->id;

        if ($loanPayment->state_id == $PendientePago){
            DB::beginTransaction();
            try {
                $payment = new Voucher;
                $payment->user_id = auth()->id();
                $payment->affiliate_id = $loanPayment->loan->disbursable_id;
                $payment->voucher_type_id = $request->voucher_type_id;
                $payment->total = $loanPayment->estimated_quota;
                $payment->payment_date = $loanPayment->estimated_date;
                $payment->paid_amount = $loanPayment->estimated_quota;
                $payment->payment_type_id = $request->payment_type_id;
                $payment->description = $request->input('description', null);
                $payment->voucher_number = $request->input('voucher_number', null);
                $voucher = $loanPayment->voucher()->create($payment->toArray());
                $loanPayment->update(['state_id' => $Pagado]);
                Util::save_record($voucher, 'datos-de-un-pago', 'registró pago : '. $voucher->code);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                return $e;
            }
            return $payment;
        }
        abort(403, 'Registro de Pago finalizado');
    }

    /**
    * Derivar en lote
    * Deriva o devuelve trámites en un lote mediante sus IDs
    * @bodyParam ids array required Lista de IDs de los trámites a derivar. Example: [1,2,3]
    * @bodyParam to_role integer required ID del rol al cual derivar o devolver. Example: 82
    * @authenticated
    * @responseFile responses/loan_payment/derivation_amortization.200.json
    */
    public function derivation_amortization(Request $request)
    {
        $PendientePago = LoanState::whereName('Pendiente de Pago')->first()->id;
        $loanPayment =  LoanPayment::whereIn('id',$request->ids)->where('role_id', '!=', $request->role_id)->where('state_id', $PendientePago)->orderBy('code');
        $derived = $loanPayment->get();
        $derived = Util::derivation($request->to_role, $derived, $loanPayment);
        return $derived;
    }

    /**
    * Impresión del Registro de Pago de Préstamo
    * Devuelve un pdf del Pago acorde a un ID de registro de pago
    * @urlParam loan_payment required ID del pago. Example: 1
    * @queryParam copies Número de copias del documento. Example: 2
    * @authenticated
    * @responseFile responses/loan_payment/print_loan_payment.200.json
    */
    public function print_loan_payment(Request $request, LoanPayment $loan_payment, $standalone = true)
    {
        $loan = LoanPayment::findOrFail($loan_payment->id)->loan;
        $procedure_modality = $loan->modality;
        $lenders = [];
        foreach ($loan->lenders as $lender) {
            $lenders[] = LoanController::verify_spouse_disbursable($lender)->disbursable;
        }
        $persons = collect([]);
        foreach ($lenders as $lender) {
            $persons->push([
                'id' => $lender->id,
                'full_name' => implode(' ', [$lender->title, $lender->full_name]),
                'identity_card' => $lender->identity_card_ext,
                'position' => 'SOLICITANTE'
            ]);
        }
        $data = [
            'header' => [
                'direction' => 'DIRECCIÓN DE ESTRATEGIAS SOCIALES E INVERSIONES',
                'unity' => 'UNIDAD DE INVERSIÓN EN PRÉSTAMOS',
                'table' => [
                    ['Tipo', $loan->modality->procedure_type->second_name],
                    ['Modalidad', $loan->modality->shortened],
                    ['Usuario', Auth::user()->username]
                ]
            ],
            'title' => 'AMORTIZACIÓN DE CUOTA',
            'loan' => $loan,
            'lenders' => collect($lenders),
            'loan_payment' => $loan_payment,
            'signers' => $persons
        ];
        $file_name = implode('_', ['pagos', $procedure_modality->shortened, $loan->code]) . '.pdf';
        $view = view()->make('loan.payments.payment_loan')->with($data)->render();
        if ($standalone) return Util::pdf_to_base64([$view], $file_name, 'legal', $request->copies ?? 1);
        return $view;
    }

    /**
    * Impresión del Voucher de Pagos
    * Devuelve un pdf del Voucher acorde a un ID de pago
    * @urlParam loanPayment required ID del pago. Example: 1
    * @queryParam copies Número de copias del documento. Example: 2
    * @authenticated
    * @responseFile responses/voucher/printvoucher.200.json
    */

    public function print_voucher(Request $request, LoanPayment $loanPayment, $standalone = true)
    {
        $loanPayment->voucher;
        $lenders = [];
        foreach ($loanPayment->loan->lenders as $lender) {
            $lenders[] = LoanController::verify_spouse_disbursable($lender)->disbursable;
        }
        $data = [
            'header' => [
                'direction' => 'DIRECCIÓN DE ESTRATEGIAS SOCIALES E INVERSIONES',
                'unity' => 'UNIDAD DE INVERSIÓN EN PRÉSTAMOS',
                'table' => [
                    ['Número de Cuota', $loanPayment->quota_number],
                    ['Código', $loanPayment->voucher->code],
                    ['Usuario', Auth::user()->username]
                ]
            ],
            'title' => 'RECIBO OFICIAL',
            'loanPayment' => $loanPayment,
            'lenders' => collect($lenders)
        ];
        $file_name = implode('_', ['voucher', $loanPayment->voucher->code]) . '.pdf';
        $view = view()->make('loan.payments.payment_voucher')->with($data)->render();
        if ($standalone) return Util::pdf_to_base64([$view], $file_name, 'letter', $request->copies ?? 1);
        return $view;
    }
}