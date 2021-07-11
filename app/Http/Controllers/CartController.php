<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use DB;

class CartController extends Controller
{

    public function addProductInCart(Request $request)
    {
      DB::table('cart')
      ->insert([
        'user_id' => Auth::guard('web')->user()->id,
        'product_id' => $request->product_id,
        'quantity' => 1
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Product has been added in cart.'
      ]);
    }

    public function removeProductFromCart(Request $request)
    {
      DB::table('cart')
      ->where([
        'user_id' => Auth::guard('web')->user()->id,
        'product_id' => $request->product_id
      ])
      ->delete();

      return response()->json([
        'success' => true,
        'message' => 'Product has been removed from cart.'
      ]);
    }

    public function setCartProductQuantity(Request $request)
    {
      DB::table('cart')
      ->where([
        'user_id' => Auth::guard('web')->user()->id,
        'product_id' => $request->product_id
      ])
      ->update([
        'quantity' => $request->quantity
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Product has been updated in cart.'
      ]);
    }

    public function getUserCart(Request $request)
    {
      $userId = Auth::guard('web')->user()->id;
      $products = DB::table('cart')
                    ->select('cart.product_id', 'cart.quantity', 'products.price')
                    ->leftJoin('products', 'cart.product_id', '=', 'products.id')
                    ->where('cart.user_id', $userId)
                    ->get();

      return response()->json([
        'products' => $products,
        'discount' => $this->calculateDiscount($products, $userId)
      ]);
    }

    protected function calculateDiscount($products, $userId)
    {
      $discountQuantity = $products->min('quantity');
      $discount = 0;

      foreach($products as $product) {
        $product->discount = 0;
        $group_item = DB::table('product_group_items')->where('product_id', $product->id)->first();

        if($group_item) {
          $userDiscount =  DB::table('user_product_groups')
          ->where([
            'id' => $group_item->group_id,
            'user_id' =>  Auth::guard('web')->user()->id
          ])
          ->first();

          if($userDiscount) {
            $product->discount = $userDiscount->discount;
          }
        }

        $discount += ($product->discount > 0) ? ($product->price * $product->discount / 100) : 0;
      }

      return (float) number_format($discount, 2);
    }
}
