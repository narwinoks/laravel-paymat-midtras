<?php

namespace App\Http\Controllers;

use App\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Storage;

class DonationController extends Controller
{
    //

    public function __construct()
{
   \Midtrans\Config::$serverKey = config('services.midtrans.serverKey');
   \Midtrans\Config::$isProduction = config('services.midtrans.isProduction');
   \Midtrans\Config::$isSanitized = config('services.midtrans.isSanitized');
   \Midtrans\Config::$is3ds = config('services.midtrans.is3ds');
}

    public function index(Request $request){
        // dd(config('services.midtrans.clientKey'));
        return view('donation');
    }

    public function store(Request $request){
        // dd($request->all());
        DB::transaction(function() use($request) {
            $donation=Donation::create([
                'donation_code' =>'SANBOX' . uniqid(),
                'donor_name' =>$request->donor_name,
                'donor_email' =>$request->donor_email,
                'donation_type' =>$request->donation_type,
                'amount' =>floatval($request->amount),
                'note'=>$request->note
            ]);

            $payload=[
                'transaction_details' =>[
                    'order_id'=>$donation->donation_code,
                    'gross_amount'=>$donation->amount
                ],
                'custom_details' =>[
                    'first_name' =>$donation->name,
                    'email' =>$donation->email
                ],
                'items_details' =>[
                    [
                        'id'=>$donation->donation_type,
                        'price'=>$donation->amount,
                        'quantity'=>1,
                        'name'     => ucwords(str_replace('_', ' ', $donation->donation_type))
                    ]
                ]
            ];
            $snapToken= \Midtrans\Snap::getSnapToken($payload);
            $donation->snap_token=$snapToken;
            $donation->save();
            $this->response['snap_token']=$snapToken;

        });

        return response()->json($this->response);

    }

    public function notification(Request $request)
    {
        $donation = Donation::first();
        $donation->setStatusSuccess();
        // try {
        //     //code...
        //     $notif = new \Midtrans\Notification();

        //     \DB::transaction(function() use($notif) {

        //       $transaction = $notif->transaction_status;
        //       $type = $notif->payment_type;
        //       $orderId = $notif->order_id;
        //       $fraud = $notif->fraud_status;
        //       $donation = Donation::where('transaction_id', $orderId)->first();

        //       if ($transaction == 'capture') {
        //         if ($type == 'credit_card') {

        //           if($fraud == 'challenge') {
        //             $donation->setStatusPending();
        //           } else {
        //             $donation->setStatusSuccess();
        //           }

        //         }
        //       } elseif ($transaction == 'settlement') {

        //         $donation->setStatusSuccess();

        //       } elseif($transaction == 'pending'){

        //           $donation->setStatusPending();

        //       } elseif ($transaction == 'deny') {

        //           $donation->setStatusFailed();

        //       } elseif ($transaction == 'expire') {

        //           $donation->setStatusExpired();

        //       } elseif ($transaction == 'cancel') {

        //           $donation->setStatusFailed();

        //       }

        //     });

        //     return;
        // } catch (\Throwable $th) {
        //     //throw $th;

        //     return $th;
        // }
    }
}
