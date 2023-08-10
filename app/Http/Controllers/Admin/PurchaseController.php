<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DefaultSetting;
use App\Models\Product;
use App\Models\Purchase_cart;
use App\Models\Purchase_details;
use App\Models\Purchase_summary;
use App\Models\Supplier;
use App\Models\Suppliers_payment_summary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseController extends Controller
{
    public function purchase (Request $request)
    {
        if ($request->ajax()) {
            $purchase_carts = "";
            $query = Purchase_cart::where('supplier_id', $request->supplier_id)
                ->leftJoin('products', 'purchase_carts.product_id', 'products.id');

            $purchase_carts = $query->select('purchase_carts.*', 'products.product_name', 'products.unit_id', 'products.brand_id')->get();

            return Datatables::of($purchase_carts)
                    ->addIndexColumn()
                    ->editColumn('brand_name', function($row){
                        return'
                        <span class="badge bg-success">'.$row->relationtobrand->brand_name.'</span>
                        ';
                    })
                    ->editColumn('unit_name', function($row){
                        return'
                        <span class="badge bg-success">'.$row->relationtounit->unit_name.'</span>
                        ';
                    })
                    ->editColumn('purchase_quantity', function($row){
                        return'
                        <input type="number" value="'.$row->purchase_quantity.'" class="form-control purchase_quantity" id="'.$row->id.'"/>
                        ';
                    })
                    ->editColumn('purchase_price', function($row){
                        return'
                        <input type="number" value="'.$row->purchase_price.'" class="form-control purchase_price" id="'.$row->id.'"/>
                        ';
                    })
                    ->editColumn('total_price', function($row){
                        return'
                        <span class="badge bg-success">'.$row->purchase_quantity * $row->purchase_price.'</span>
                        ';
                    })
                    ->addColumn('action', function($row){
                        $btn = '
                            <button type="button" id="'.$row->id.'" class="btn btn-danger btn-sm deleteBtn"><i class="fa-solid fa-trash-can"></i></button>
                        ';
                        return $btn;
                    })
                    ->rawColumns(['purchase_quantity', 'unit_name', 'brand_name', 'purchase_price', 'total_price', 'action'])
                    ->make(true);
        }

        $suppliers = Supplier::where('status', 'Active')->get();
        $categories = Category::where('status', 'Active')->get();
        return view('admin.purchase.create', compact('suppliers', 'categories'));
    }

    public function getPurchaseProductList(Request $request)
    {
        $send_products = "<option value=''>--Select Product--</option>";
        $products = Product::where('category_id', $request->category_id)->get();
        foreach ($products as $product) {
            $send_products .= "<option value='$product->id'>$product->product_name</option>";
        }
        return response()->json($send_products);
    }

    public function getPurchaseProductDetails(Request $request)
    {
        $product = Product::where('id', $request->product_id)->first();
        $purchase_price = $product->purchase_price;
        $product_stock = ($product->purchase_quantity-$product->selling_quantity);
        return response()->json([
            'product_stock' => $product_stock,
            'purchase_price' => $purchase_price,
        ]);
    }

    public function storePurchaseProductCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            '*' => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 400,
                'error'=> $validator->errors()->toArray()
            ]);
        }else{
            $exists = Purchase_cart::where('supplier_id', $request->supplier_id)
                        ->where('product_id', $request->product_id)->exists();
            if($exists){
                return response()->json([
                    'status' => 401,
                    'message' => 'This product already added.',
                ]);
            }else{
                Purchase_cart::insert([
                    'supplier_id' => $request->supplier_id,
                    'product_id' => $request->product_id,
                    'purchase_quantity' => 1,
                    'purchase_price' => $request->purchase_price,
                    'created_at' => Carbon::now(),
                ]);

                $sub_total = 0;
                foreach(Purchase_cart::where('supplier_id', $request->supplier_id)->get() as $cart){
                    $sub_total += ($cart->purchase_quantity*$cart->purchase_price);
                };

                return response()->json([
                    'status' => 200,
                    'sub_total' => $sub_total,
                    'message' => 'Purchase product added successfully.',
                ]);
            }
        }
    }

    public function updatePurchaseProductCart(Request $request)
    {
        Purchase_cart::where('id', $request->cart_id)->update([
            'purchase_quantity' => $request->purchase_quantity,
            'purchase_price' => $request->purchase_price,
        ]);

        $sub_total = 0;
        foreach(Purchase_cart::where('supplier_id', $request->supplier_id)->get() as $cart){
            $sub_total += ($cart->purchase_quantity*$cart->purchase_price);
        };
        return response()->json($sub_total);
    }

    public function getPurchaseCartSubtotal(Request $request)
    {
        $sub_total = 0;
        foreach(Purchase_cart::where('supplier_id', $request->supplier_id)->get() as $cart){
            $sub_total += ($cart->purchase_quantity*$cart->purchase_price);
        };
        return response()->json($sub_total);
    }

    public function purchaseCartProductDelete(Request $request)
    {
        Purchase_cart::where('id', $request->cart_id)->delete();

        $sub_total = 0;
        foreach(Purchase_cart::where('supplier_id', $request->supplier_id)->get() as $cart){
            $sub_total += ($cart->purchase_quantity*$cart->purchase_price);
        };

        return response()->json([
            'sub_total' => $sub_total,
            'message' => 'Purchase cart item delete successfully.',
        ]);
    }

    public function purchaseStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            '*' => 'required',
            'discount' => 'nullable',
            'payment_method' => 'nullable',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 400,
                'error'=> $validator->errors()->toArray()
            ]);
        }else{
            $products_data =  Purchase_cart::where('supplier_id', $request->supplier_id);

            if(!$products_data->exists()){
                return response()->json([
                    'status' => 401,
                    'message' => 'Purchase product not added.',
                ]);
            }else{
                if($request->payment_status != 'Unpaid' && $request->payment_method == NULL){
                    return response()->json([
                        'status' => 402,
                        'message' => 'The payment_method field is required.',
                    ]);
                }else{
                    $purchase_invoice_no = "PI-".Purchase_summary::max('id')+1;

                    Purchase_summary::insert([
                        'purchase_invoice_no' => $purchase_invoice_no,
                        'supplier_id' => $request->supplier_id,
                        'sub_total' => $request->sub_total,
                        'discount' => $request->discount,
                        'grand_total' => $request->grand_total,
                        'payment_status' => $request->payment_status,
                        'payment_amount' => $request->payment_amount,
                        'purchase_agent_id' => Auth::user()->id,
                        'branch_id' => Auth::user()->branch_id,
                        'created_at' => Carbon::now(),
                    ]);

                    Suppliers_payment_summary::insert([
                        'supplier_id' => $request->supplier_id,
                        'purchase_invoice_no' => $purchase_invoice_no,
                        'grand_total' => $request->grand_total,
                        'payment_status' => $request->payment_status,
                        'payment_method' => $request->payment_method,
                        'payment_amount' => $request->payment_amount,
                        'payment_agent_id' => Auth::user()->id,
                        'created_at' => Carbon::now(),
                    ]);

                    $cart_products = $products_data->get();
                    foreach($cart_products as $cart_product){
                        Purchase_details::insert([
                            'purchase_invoice_no' => $purchase_invoice_no,
                            'product_id' => $cart_product->product_id,
                            'purchase_quantity' => $cart_product->purchase_quantity,
                            'purchase_price' => $cart_product->purchase_price,
                            'branch_id' => Auth::user()->branch_id,
                        ]);

                        Product::where('id', $cart_product->product_id)->update([
                            'purchase_price' => $cart_product->purchase_price,
                        ]);

                        Product::where('id', $cart_product->product_id)->increment('purchase_quantity', $cart_product->purchase_quantity);

                        Purchase_cart::find($cart_product->id)->delete();
                    }

                    // Send SMS
                    $supplier = Supplier::find($request->supplier_id);

                    $url = "https://bulksmsbd.net/api/smsapi";
                    $api_key = env('SMS_API_KEY');
                    $senderid = env('SMS_SENDER_ID');
                    $number = "$supplier->supplier_phone_number";
                    $message = "Hello $supplier->supplier_name.
                                Your invoice no $purchase_invoice_no.
                                Your selling cost $request->grand_total.
                                Our payment amount $request->payment_amount, Our payment method $request->payment_method.
                                Thanks for selling to our store.
                                ";
                    $data = [
                        "api_key" => $api_key,
                        "senderid" => $senderid,
                        "number" => $number,
                        "message" => $message
                    ];
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    // return $response;

                    return response()->json([
                        'status' => 200,
                        'purchase_invoice_no' => Crypt::encrypt($purchase_invoice_no),
                        'message' => 'Product purchase successfully.',
                    ]);
                }
            }
        }
    }

    public function purchaseList(Request $request)
    {
        if ($request->ajax()) {
            $purchase_summaries = "";
            $query = Purchase_summary::where('branch_id', Auth::user()->branch_id)
                                    ->leftJoin('suppliers', 'purchase_summaries.supplier_id', 'suppliers.id');

            if($request->supplier_id){
                $query->where('purchase_summaries.supplier_id', $request->supplier_id);
            }
            if($request->payment_status){
                $query->where('purchase_summaries.payment_status', $request->payment_status);
            }

            $purchase_summaries = $query->select('purchase_summaries.*', 'suppliers.supplier_name')
                                    ->orderBy('created_at', 'DESC')->get();

            return Datatables::of($purchase_summaries)
                    ->addIndexColumn()
                    ->editColumn('payment_status', function($row){
                        if($row->payment_status != "Paid"){
                            return'
                            <span class="badge bg-warning">'.$row->payment_status.'</span>
                            <button type="button" id="'.$row->purchase_invoice_no.'" class="btn btn-primary btn-sm paymentBtn" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="fa-regular fa-credit-card"></i></button>
                            ';
                        }else{
                            return'
                            <span class="badge bg-success">'.$row->payment_status.'</span>
                            ';
                        }
                    })
                    ->addColumn('action', function($row){
                        $btn = '
                            <a href="'.route('purchase.invoice', Crypt::encrypt($row->purchase_invoice_no) ).'" target="_blank" class="btn btn-primary btn-sm"><i class="fa-solid fa-download"></i></a>
                        ';
                        return $btn;
                    })
                    ->rawColumns(['payment_status', 'action'])
                    ->make(true);
        }

        $suppliers = Supplier::all();
        return view('admin.purchase.index', compact('suppliers'));
    }

    public function supplierPayment($purchase_invoice_no)
    {
        $purchase_summary = Purchase_summary::where('purchase_invoice_no', $purchase_invoice_no)->first();
        return response()->json($purchase_summary);
    }

    public function supplierPaymentUpdate(Request $request, $purchase_invoice_no)
    {

        $validator = Validator::make($request->all(), [
            '*' => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 400,
                'error'=> $validator->errors()->toArray()
            ]);
        }else{
            $purchase_summary = Purchase_summary::where('purchase_invoice_no', $purchase_invoice_no);
            $due_amount = $purchase_summary->first()->grand_total - $purchase_summary->first()->payment_amount;
            if($request->payment_amount > $due_amount){
                return response()->json([
                    'status' => 401,
                    'message' => 'Payment quantity greater than due amount',
                ]);
            }else{
                $purchase_summary->increment('payment_amount', $request->payment_amount);
                $purchase_summary->update([
                    'payment_status' => $request->payment_status,
                ]);
                Suppliers_payment_summary::insert([
                    'supplier_id' => $request->supplier_id,
                    'purchase_invoice_no' => $request->purchase_invoice_no,
                    'grand_total' => $request->grand_total,
                    'payment_status' => $request->payment_status,
                    'payment_method' => $request->payment_method,
                    'payment_amount' => $request->payment_amount,
                    'payment_agent_id' => Auth::user()->id,
                    'created_at' => Carbon::now(),
                ]);

                // Send SMS
                $supplier = Supplier::find($request->supplier_id);
                $before_payment_amount = $purchase_summary->first()->payment_amount;

                $url = "https://bulksmsbd.net/api/smsapi";
                $api_key = env('SMS_API_KEY');
                $senderid = env('SMS_SENDER_ID');
                $number = "$supplier->supplier_phone_number";
                $message = "Hello $supplier->supplier_name.
                            Your invoice no $request->purchase_invoice_no.
                            Your selling cost $request->grand_total.
                            Our before payment amount $before_payment_amount.
                            Our due amount $due_amount.
                            Now our payment amount $request->payment_amount, Our payment method $request->payment_method.
                            Thanks for Selling to our store.
                            ";
                $data = [
                    "api_key" => $api_key,
                    "senderid" => $senderid,
                    "number" => $number,
                    "message" => $message
                ];
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);
                curl_close($ch);
                // return $response;

                return response()->json([
                    'status' => 200,
                    'message' => 'Supplier payment successfully.',
                ]);
            }
        }
    }

    public function purchaseInvoice($purchase_invoice_no){
        $default_setting = DefaultSetting::first();
        $purchase_invoice_no = Crypt::decrypt($purchase_invoice_no);
        $purchase_summary = Purchase_summary::where('purchase_invoice_no', $purchase_invoice_no)->first();
        $purchase_details = Purchase_details::where('purchase_invoice_no', $purchase_invoice_no)->get();
        $payment_summaries = Suppliers_payment_summary::where('purchase_invoice_no', $purchase_invoice_no)->get();
        return view('admin.purchase.invoice', compact('default_setting', 'purchase_summary', 'purchase_details', 'payment_summaries'));
    }
}
