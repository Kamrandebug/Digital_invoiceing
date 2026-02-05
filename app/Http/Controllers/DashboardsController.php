<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardsController extends Controller
{
    public function index2()
    {
        return view('pages.dashboards.index2');
    }

    public function products()
    {
        return view('pages.dashboards.products');
    }

    public function product_details()
    {
        return view('pages.dashboards.product-details');
    }

    public function cart()
    {
        return view('pages.dashboards.cart');
    }

    public function checkout()
    {
        return view('pages.dashboards.checkout');
    }

    public function customers_list()
    {
        return view('pages.dashboards.customers-list');
    }

    public function orders()
    {
        return view('pages.dashboards.orders');
    }

    public function orders_details()
    {
        return view('pages.dashboards.orders-details');
    }

    public function add_product()
    {
        return view('pages.dashboards.add-product');
    }
}
