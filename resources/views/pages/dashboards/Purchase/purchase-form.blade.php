@extends('layouts.master')

@section('content')
@include('pages.dashboards.Purchase._purchase-form-partial', [
    'mode' => $mode,
    'purchase' => $purchase ?? null,
    'items' => $items ?? collect(),
    'suppliers' => $suppliers ?? [],
    'products' => $products ?? [],
    'inModal' => $inModal ?? false,
])
@endsection
