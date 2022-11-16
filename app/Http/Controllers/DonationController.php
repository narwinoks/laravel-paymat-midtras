<?php

namespace App\Http\Controllers;

use App\Donation;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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


    public function index(){
        $donations=Donation::orderBy('id','DESC')->paginate(8);
        return view('welcome',compact('donations'));
    }
    public function create(Request $request){
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

        $notif = new \Midtrans\Notification();

        $transaction = $notif->transaction_status;
        $type = $notif->payment_type;
        $fraud = $notif->fraud_status;
        // $donation = Donation::where('transaction_id',$order_id)-> first();
        // $donation->setStatusSuccess();

        // $donation2=Donation::where('donation_code',$order_id)->first();
        // $donation3 = Donation::first();
        // $donation2->setStatusSuccess();
        // $donation3->setStatusExpired();

        // Session::set('variableName', $value);
        // session()->put('id', $order_id);
        // session()->put('id2', "SANBOX6374ffaea843b");

        // Session::pu

        // return "oke";
        // $notif = new \Midtrans\Notification();
        // $orderId = $notif->order_id;
        // session()->put('testdata', $notif);
        // // dd($);
        // $donation = Donation::where('donation_code',$orderId)-> first();

        // return response()->json("",Response::HTTP_OK);
        // try {
        //     //code...

            \DB::transaction(function() use($notif) {
             $order_id = $notif->order_id;
              $transaction = $notif->transaction_status;
              $type = $notif->payment_type;
              $fraud = $notif->fraud_status;
              $donation = Donation::where('donation_code', $order_id)->first();

              if ($transaction == 'capture') {
                if ($type == 'credit_card') {

                  if($fraud == 'challenge') {
                    $donation->setStatusPending();
                  } else {
                    $donation->setStatusSuccess();
                  }

                }
              } elseif ($transaction == 'settlement') {

                $donation->setStatusSuccess();

              } elseif($transaction == 'pending'){

                  $donation->setStatusPending();

              } elseif ($transaction == 'deny') {

                  $donation->setStatusFailed();

              } elseif ($transaction == 'expire') {

                  $donation->setStatusExpired();

              } elseif ($transaction == 'cancel') {

                  $donation->setStatusFailed();

              }

            });

            return;
        // } catch (\Throwable $th) {
        //     //throw $th;

        //     return $th;
        // }
    }
}
