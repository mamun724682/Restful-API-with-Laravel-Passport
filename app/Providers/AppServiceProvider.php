<?php

namespace App\Providers;

use App\User;
use App\Product;
use App\Mail\UserCreated;
use App\Mail\UserMailChanged;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);


        //When user create account ,need to verify email
        User::created(function($user)
        {
            retry(5, function() use ($user)
            {
            Mail::to($user)->send(new UserCreated($user));
            }, 100);
        });

        //When user update email ,need to verify email
        User::updated(function($user)
        {
            if ($user->isDirty('email')) {
                retry(5, function() use ($user)
                {
                Mail::to($user)->send(new UserMailChanged($user));
                }, 100);
            }
        });


        Product::updated(function($product)
        {
            if ($product->quantity == 0 && $product->isAvailable()) {
                $product->status = Product::UNAVAILABLE_PRODUCT;

                $product->save();
                
            } elseif ($product->quantity > 0 && !$product->isAvailable()) {
                $product->status = Product::AVAILABLE_PRODUCT;

                $product->save();
            }
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
