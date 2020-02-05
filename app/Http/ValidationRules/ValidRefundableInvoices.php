<?php
/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2020. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Http\ValidationRules;

use App\Models\Invoice;
use App\Models\Payment;
use App\Utils\Traits\MakesHash;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class ValidRefundableInvoices
 * @package App\Http\ValidationRules
 */
class ValidRefundableInvoices implements Rule
{
    use MakesHash;

    /**
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    
    private $error_msg;

    public function passes($attribute, $value)
    {

        //\Log::error(request()->input('id'));

        $payment = Payment::whereId(request()->input('id'))->first();

        if(!$payment){
            $this->error_msg = "Payment couldn't be retrieved cannot be refunded ";
                return false;
        }

        if(request()->has('amount') && (request()->input('amount') > ($payment->amount - $payment->refunded))){
            $this->error_msg = "Attempting to refunded more than payment amount, enter a value equal to or lower than the payment amount of ". $payment->amount;
            return false;
        }

        /*If no invoices has been sent, then we apply the payment to the client account*/
        $invoices = [];

        if (is_array($value)) {
            $invoices = Invoice::whereIn('id', array_column($value, 'invoice_id'))->company()->get();
        }
        else
            return true;

        foreach ($invoices as $invoice) {
            if (! $invoice->isRefundable()) {
                $this->error_msg = "Invoice id ".$invoice->hashed_id ." cannot be refunded";
                return false;
            }


            foreach ($value as $val) {
               if ($val['invoice_id'] == $invoice->id) {

                    //$pivot_record = $invoice->payments->where('id', $invoice->id)->first();
                    $pivot_record = $payment->paymentables->where('paymentable_id', $invoice->id)->first();

                    if($val['amount'] > ($pivot_record->amount - $pivot_record->refunded)) {
                        $this->error_msg = "Attempting to refund ". $val['amount'] ." only ".($pivot_record->amount - $pivot_record->refunded)." available for refund";
                        return false;
                    }
               }
            }

        }

        return true;
    }

    /**
     * @return string
     */
    public function message()
    {
        return $this->error_msg;
    }
}
