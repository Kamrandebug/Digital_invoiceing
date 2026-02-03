
@extends('layouts.master')

@section('styles')



@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Chartjs</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="javascript:void(0);">
                                        Charts
                                    </a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Chartjs</li>
                            </ol>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start::row-1 -->
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Chartjs Line Chart</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartjs-line" class="chartjs-chart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Chartjs Bar Chart</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartjs-bar" class="chartjs-chart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Chartjs Pie Chart</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartjs-pie" class="chartjs-chart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Chartjs Doughnut Chart</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartjs-doughnut" class="chartjs-chart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Chartjs Mixed Chart</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartjs-mixed" class="chartjs-chart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Chartjs Polar Chart</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartjs-polar" class="chartjs-chart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Chartjs Radial Chart</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartjs-radar" class="chartjs-chart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Chartjs Scatter Chart</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartjs-scatter" class="chartjs-chart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Chartjs Bubble Chart</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartjs-bubble" class="chartjs-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--End::row-1 -->

@endsection

@section('scripts')
	
        <!-- Chartjs Chart JS -->
        <script src="{{asset('build/assets/libs/chart.js/chart.umd.js')}}"></script>

        <!-- Imternal Chartjs JS -->
        @vite('resources/assets/js/chartjs-charts.js')

@endsection
