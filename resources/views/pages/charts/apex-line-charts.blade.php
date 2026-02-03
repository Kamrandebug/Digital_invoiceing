
@extends('layouts.master')

@section('styles')

        <link rel="stylesheet" href="{{asset('build/assets/libs/apexcharts/apexcharts.css')}}">

@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Apex Line Charts</h1>
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
                                <li class="breadcrumb-item active" aria-current="page">Apex Line Charts</li>
                            </ol>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start::row-1 -->
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Basic Line Chart</div>
                                </div>
                                <div class="card-body">
                                    <div id="line-chart"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Line Chart With Data Labels</div>
                                </div>
                                <div class="card-body">
                                    <div id="line-chart-datalabels"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Zoomable Time Series</div>
                                </div>
                                <div class="card-body">
                                    <div id="zoom-chart"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Line With Annotations</div>
                                </div>
                                <div class="card-body">
                                    <div id="annotation-chart"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Brush Chart</div>
                                </div>
                                <div class="card-body">
                                    <div id="brush-chart1"></div>
                                    <div id="brush-chart"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">StepLine Chart</div>
                                </div>
                                <div class="card-body">
                                    <div id="stepline-chart"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Gradient Line Chart</div>
                                </div>
                                <div class="card-body">
                                    <div id="gradient-chart"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Missing/Null Values Chart</div>
                                </div>
                                <div class="card-body">
                                    <div id="null-chart"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Real Time Chart</div>
                                </div>
                                <div class="card-body">
                                    <div id="dynamic-chart"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Dashed Line Chart</div>
                                </div>
                                <div class="card-body">
                                    <div id="dashed-chart"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Syncing Charts</div>
                                </div>
                                <div class="card-body">
                                    <div id="chart-line"></div>
                                    <div id="chart-line2"></div>
                                    <div id="chart-area"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--End::row-1 -->

@endsection

@section('scripts')
	
        <!-- Apex Charts JS -->
        <script src="{{asset('build/assets/libs/apexcharts/apexcharts.min.js')}}"></script>

        <!-- Used In Zoomable TIme Series Chart -->
        <script src="{{asset('build/assets/dataseries.js')}}"></script>

        <!---Used In Annotations Chart-->
        <script src="{{asset('build/assets/apexcharts-stock-prices.js')}}"></script>

        <!-- Internal Apex Line Charts JS -->
        @vite('resources/assets/js/apexcharts-line.js')

@endsection
