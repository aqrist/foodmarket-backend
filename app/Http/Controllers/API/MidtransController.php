<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Notification;

class MidtransController extends Controller
{
    public function callback(Request $request)
    {
        // set config
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');


        // create instance midtrans notif
        $notification = new Notification();

        // assign ke variable
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraud_status;
        $order_id = $notification->order_id;

        // find transaction
        $transaction = Transaction::findOrFail($order_id);

        // handle notification status midtrans
        if ($status == 'capture') {
            if ($type == 'credit_card') {
                if ($fraud == 'challenge') {
                    $transaction->status = 'PENDING';
                } else {
                    $transaction->status = 'SUCCESS';
                }
            } else if ($status == 'settlement') {
                $transaction->status = 'SUCCESS';
            } else if ($status == 'pending') {
                $transaction->status = 'PENDING';
            } else if ($status == 'deny') {
                $transaction->status = 'CANCELLED';
            } else if ($status == 'expire') {
                $transaction->status = 'CANCELLED';
            }else if ($status == 'cancel') {
                $transaction->status = 'CANCELLED';
            }
        }

        // simpan transaksi
        $transaction->save();
    }

    public function success()
    {
        return view('midtrans.success');
    }

    public function unfinish()
    {
        return view('midtrans.unfinish');
    }

    public function error()
    {
        return view('midtrans.error');
    }
}
