<?php

namespace App\Http\Controllers;
use Auth;
use Illuminate\Http\Request;
use App\Product;
use Session;
use App\Cart;
use Stripe\Charge;
use Stripe\Stripe;
use App\Order;
class ProductController extends Controller
{
    public function getIndex(){
    	$products = Product::all();
    	return view('shop.index',['products'=>
    		$products]);
	}
	
	public function getAddToCart(Request $request,$id){
		$product = Product::find($id);
		$oldCart = Session::has('cart') ? Session::get('cart') : null;
		$cart = new Cart($oldCart);
		$cart->add($product, $product->id);
		$request->session()->put('cart', $cart);
		return redirect()->route('product.index');
	} 

	public function getCart(){
		if(!Session::has('cart')){
			return view('shop.shopping-cart');
			
		}
		else{
		$oldCart= Session::get('cart');
		$cart = new Cart($oldCart);
		return view('shop.shopping-cart', ['products' => $cart->items, 'totalPrice' => $cart->totalPrice ]);
		
	
	}

	}

	public function getCheckout(){

		if (Auth::check()) {
			if(!Session::has('cart')){
			return view('shop.shopping-cart');
		   }
				$oldCart= Session::get('cart');
				$cart = new Cart($oldCart);
				$total= $cart->totalPrice;
				return view('shop.checkout',['total' => $total ]);
		}

		else{
			$checkoutUrl="checkoutUrl";
			Session::put('checkout',$checkoutUrl);
			return view('user.signin');
			
		}

		
	}
	
	public function postCheckout(Request $request){
					

		 if(!Session::has('cart')){
		 return redirect()->route('shop.shopping-cart');
		 }

		$oldCart= Session::get('cart');
	 	$cart = new Cart($oldCart);
		Stripe::setApiKey('sk_test_eK517O2ElLxWvgzvJtRt5xcq00OQuta03n');
	 	try{
 	 		$charge=Charge::create(array(
	 			"amount" => $cart->totalPrice * 100,
	 			"currency" => "usd",
	 			"source" => $request->input('stripeToken'),
	 			"description" => "Test Charge"
	 		));
	 		$order = new Order();
	 		$order->cart=serialize($cart);
	 		$order->address=$request->input('address');
	 		$order->name=$request->input('name');
	 		$order->payment_id=$charge->id;
	 		Auth::user()->orders()->save($order);
	 	}catch(\Exception $e){
	 		return redirect()->route('checkout')->with('error', $e->getMessage());
	 	}

	 	Session::forget('cart');
	 	return redirect()->route('product.index')->with('success', 'Successfully Purchased Products');
  }
}


