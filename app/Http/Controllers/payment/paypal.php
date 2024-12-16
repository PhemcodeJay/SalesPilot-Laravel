<?php

namespace App\Http\Controllers;

use App\Services\PayPalService;
use Illuminate\Http\Request;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;

class PayPalController extends Controller
{
    private $paypal;

    public function __construct(PayPalService $paypal)
    {
        $this->paypal = $paypal;
    }

    // Create a payment
    public function createPayment()
    {
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $amount = new Amount();
        $amount->setTotal('10.00') // Total amount
              ->setCurrency('USD'); // Currency

        $transaction = new Transaction();
        $transaction->setAmount($amount)
                    ->setDescription('Payment description');

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(route('paypal.success')) // Redirect on success
                     ->setCancelUrl(route('paypal.cancel')); // Redirect on cancel

        $payment = new Payment();
        $payment->setIntent('sale')
                ->setPayer($payer)
                ->setTransactions([$transaction])
                ->setRedirectUrls($redirectUrls);

        try {
            $payment->create($this->paypal->getApiContext());
            return redirect($payment->getApprovalLink());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred');
        }
    }

    // Execute the payment
    public function executePayment(Request $request)
    {
        $paymentId = $request->query('paymentId');
        $payerId = $request->query('PayerID');

        if (!$paymentId || !$payerId) {
            return redirect()->back()->with('error', 'Payment failed');
        }

        $payment = Payment::get($paymentId, $this->paypal->getApiContext());
        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        try {
            $result = $payment->execute($execution, $this->paypal->getApiContext());
            return redirect()->route('home')->with('success', 'Payment successful');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Payment failed');
        }
    }

    // Cancel the payment
    public function cancelPayment()
    {
        return redirect()->route('home')->with('error', 'Payment canceled');
    }
}
