@extends('layouts.app')



@section('content')



    <div class="content-wrapper">

        <section class="content-header">

            <div class="container-fluid">

                <div class="row mb-2">

                    <div class="col-sm-6">

                        <h1>Prix</h1>

                    </div>

                    <div class="col-sm-6">

                        <ol class="breadcrumb float-sm-right">

                            <li class="breadcrumb-item"><a href="#">Acceuil</a></li>

                            <li class="breadcrumb-item"><a href="{{ route('prix.index') }}">prix</a></li>

                            <li class="breadcrumb-item active">Suppression prix</li>

                        </ol>

                    </div>

                </div>

            </div><!-- /.container-fluid -->

        </section>





        <section class="content">

            <div class="container">

                <div class="row">

                    <div class="col-md-3"></div>

                    <div class="col-md-6">

                        <div class="card card-warning bg-warning">

                            <div class="card-header">

                                <h3 class="card-title">Suppression Prix</h3>

                            </div>

                                <div class="card-body">

                                    Êtes vous sûr de vouloir supprimer le prix {{ $prix->prix }} 

                                </div>

                                <div class="card-footer">

                                    <div class="row justify-content-center">

                                        <div class="col-sm-4">

                                            <a href="{{ route('prix.index') }}" class="btn btn-sm btn-secondary btn-block">

                                                <i class="fa-solid fa-circle-left"></i>

                                                {{ __('Retour') }}

                                            </a>

                                        </div>

                                        <div class="col-sm-4">

                                            <a class="btn btn-sm btn-success btn-block"  href="{{ route('prix.destroy', ['prix'=>$prix->id]) }}">

                                                {{ __('Supprimer') }}

                                                <i class="fa-solid fa-trash-can"></i>

                                            </a>

                                        </div>

                                    </div>

                                </div>

                        </div>

                    </div>

                    <div class="col-md-3"></div>

                </div>

            </div>

        </section>

    </div>



@endsection;

