
@extends('layouts.master')

@section('styles')

        <link rel="stylesheet" href="{{asset('build/assets/libs/apexcharts/apexcharts.css')}}">

@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Apex Area Charts</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="javascript:void(0);">
                                        Charts
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="javascript:void(0);">
                                        Apex Charts
                                    </a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Apex Area Charts</li>
                            </ol>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start::row-1 -->
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Basic Area Chart</div>
                                </div>
                                <div class="card-body">
                                    <div id="area-basic"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Spline Area Chart</div>
                                </div>
                                <div class="card-body">
                                    <div id="area-spline"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Area Chart With Negative Values</div>
                                </div>
                                <div class="card-body">
                                    <div id="area-negative"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Selection-Github Style Chart</div>
                                </div>
                                <div class="card-body">
                                    <div id="chart-months"></div>
                                    <div class="github-style d-flex align-items-center">
                                        <div class="me-2">
                                            <img class="userimg rounded" src="{{asset('build/assets/images/faces/1.jpg')}}"
                                                data-hovercard-user-id="634573" alt="" width="38" height="38">
                                        </div>
                                        <div class="userdetails lh-1">
                                            <a class="username fw-medium fs-14">coder</a>
                                            <span class="cmeta d-block mt-1">
                                                <span class="commits"></span> commits
                                            </span>
                                        </div>
                                    </div>
                                    <div id="chart-years"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Stacked Area Chart</div>
                                </div>
                                <div class="card-body">
                                    <div id="area-stacked"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Irregular Time Series Chart</div>
                                </div>
                                <div class="card-body">
                                    <div id="area-irregular"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Area Chart With Null Values</div>
                                </div>
                                <div class="card-body">
                                    <div id="area-null"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header d-flex">
                                    <div class="card-title">Area Chart-Datetime X-Axis Chart</div>
                                    <div class="btn-group ms-auto">
                                        <button class="btn btn-primary btn-sm" id="one_month">1M</button>
                                        <button class="btn btn-primary btn-sm" id="six_months">6M</button>
                                        <button class="btn btn-primary btn-sm" id="one_year">1Y</button>
                                        <button class="btn btn-primary btn-sm" id="all">ALL</button>
                                        <!-- <button class="btn btn-primary btn-sm" id="ytd">ALL</button> -->
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="area-datetime"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--End::row-1 -->

@endsection

@section('scripts')
	
        <!-- Apex Charts JS -->
        <script src="{{asset('build/assets/libs/apexcharts/apexcharts.min.js')}}"></script>

        <!---Used In Basic Area Chart-->
        <script src="{{asset('build/assets/apexcharts-stock-prices.js')}}"></script>

        <!-- Used For Secection-Github Style Chart -->
        <script src="{{asset('build/assets/apex-github-data.js')}}"></script>

        <!-- Used For Irregular Time Series Chart -->
        <script src="{{asset('build/assets/apexcharts-irregulardata.js')}}"></script>
        <script src="{{asset('build/assets/libs/moment/moment.js')}}"></script>

        <!-- Internal Apex Area Charts JS -->
        @vite('resources/assets/js/apexcharts-area.js')

@endsection
