
@extends('layouts.master')

@section('styles')

        <!-- Plyr CSS -->
        <link rel="stylesheet" href="{{asset('build/assets/libs/plyr/plyr.css')}}">

@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Media Player</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Advanced UI</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Media Player</li>
                            </ol>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start::row-1 -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">
                                        VIDEO PLAYER
                                    </div>
                                </div>
                                <div class="card-body px-2 px-sm-3">
                                    <video id="player" playsinline controls data-poster="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-HD.jpg">
                                        <source src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-576p.mp4" type="video/mp4" >
                                        <source src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-720p.mp4" type="video/mp4" >
                                        <source src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-1080p.mp4" type="video/mp4" >
                                    </video>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <div class="card custom-card overflow-hidden">
                                <div class="card-header">
                                    <div class="card-title">YOUTUBE VIDEO</div>
                                </div>
                                <div class="card-body">
                                    <div class="plyr__video-embed" id="player1">
                                        <iframe width="560" height="315" src="https://www.youtube.com/embed/-lDlbQ7DiCI?si=EerYCepJFDTb5xOl" title="YouTube video player" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">
                                        AUDIO PLAYER
                                    </div>
                                </div>
                                <div class="card-body px-0 px-sm-3">
                                    <audio id="player2" controls>
                                        <source src="{{asset('build/assets/audio/perfect-beauty.mp3')}}" type="audio/mp3" >
                                    </audio>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--End::row-1 -->

@endsection

@section('scripts')
	
        <!-- Plyr JS -->
        <script src="{{asset('build/assets/libs/plyr/plyr.min.js')}}"></script>

        <!-- Internal Plyr JS -->
        @vite('resources/assets/js/media-player.js')

@endsection
