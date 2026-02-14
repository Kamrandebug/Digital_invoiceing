
@extends('layouts.master')

@section('styles')

        <!-- Jsvector Maps -->
        <link rel="stylesheet" href="{{asset('build/assets/libs/jsvectormap/jsvectormap.min.css')}}">

@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Vector Maps</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Maps</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Vector Maps</li>
                            </ol>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start::row-1 -->
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Basic Vector Map</div>
                                </div>
                                <div class="card-body">
                                    <div id="vector-map"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Map With Markers</div>
                                </div>
                                <div class="card-body">
                                    <div id="marker-map"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Map With Image Markers</div>
                                </div>
                                <div class="card-body px-1 px-sm-3">
                                    <div id="marker-image-map"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Map With Lines</div>
                                </div>
                                <div class="card-body">
                                    <div id="lines-map"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">US Vector Map</div>
                                </div>
                                <div class="card-body">
                                    <div id="us-map"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Russia Vector Map</div>
                                </div>
                                <div class="card-body">
                                    <div id="russia-map"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Spain Vector Map</div>
                                </div>
                                <div class="card-body">
                                    <div id="spain-map"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Canada Vector Map</div>
                                </div>
                                <div class="card-body">
                                    <div id="canada-map"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--End::row-1 -->

@endsection

@section('scripts')
	
        <!-- JSVector Maps JS -->
        <script src="{{asset('build/assets/libs/jsvectormap/jsvectormap.min.js')}}"></script>

        <!-- JSVector Maps MapsJS -->
        <script src="{{asset('build/assets/libs/jsvectormap/maps/world-merc.js')}}"></script>
        @vite('resources/assets/js/us-merc-en.js')
        @vite('resources/assets/js/russia.js')
        @vite('resources/assets/js/spain.js')
        @vite('resources/assets/js/canada.js')
        @vite('resources/assets/js/jsvectormap.js')

@endsection
