<?php

namespace App\Http\Controllers;

use App\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        \DB::transaction(function() use($request) {
            $donation=Donation::create([
                'donor_name' =>$request->donor_name,
                'donor_email' =>$request->donor_email,
                'donation_type' =>$request->donation_type,
                'amount' =>floatval($request->amount),
                'note'=>$request->note
            ]);

            $payload=[
                'transaction_details' =>[
                    'order_id'=>'SANBOX' . uniqid(),
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

    public function notification(){

    }
}
