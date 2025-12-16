<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Store a newly created payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $orderId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,gcash,bank_transfer,check,other',
            'payment_reference' => 'nullable|string|max:255',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if payment amount exceeds remaining balance
        $totalPaid = $order->payments()->sum('amount');
        $remainingBalance = $order->total_amount - $totalPaid;

        if ($validated['amount'] > $remainingBalance) {
            return redirect()->back()
                ->withErrors(['amount' => "Payment amount (₱{$validated['amount']}) exceeds remaining balance (₱{$remainingBalance})."])
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'payment_date' => $validated['payment_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update order payment status if fully paid
            $newTotalPaid = $order->payments()->sum('amount');
            if ($newTotalPaid >= $order->total_amount) {
                $order->update(['payment_status' => 'paid']);
            } else {
                $order->update(['payment_status' => 'unpaid']);
            }

            DB::commit();

            return redirect()->back()
                ->with('success', 'Payment recorded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to record payment: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified payment.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        $order = $payment->order;

        DB::beginTransaction();
        try {
            $payment->delete();

            // Update order payment status
            $totalPaid = $order->payments()->sum('amount');
            if ($totalPaid >= $order->total_amount) {
                $order->update(['payment_status' => 'paid']);
            } else {
                $order->update(['payment_status' => 'unpaid']);
            }

            DB::commit();

            return redirect()->back()
                ->with('success', 'Payment deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete payment: ' . $e->getMessage()]);
        }
    }
}
