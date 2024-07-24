@extends('layouts.app')
@section('content')

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <h1 class="pb-3">ETAT DE LIVRAISON D'UNE PERIODE</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('welcome') }}">Accueil</a></li>
                            <li class="breadcrumb-item active">Etat des livraisons</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-secondary">
                            <div class="card-body">
                                <form method="post" id="form_bc" action="{{route('edition.postEtatLivraisonPeriode')}}">
                                    @csrf
                                    <div class="row no-print" >
                                        <div class="col-1"></div>
                                        <div class="col-4">
                                            <div class="form-group">
                                                <label for="">Date début</label>
                                                <input type="date" class="form-control" name="debut" value="{{old('debut')}}" required>
                                            </div>
                                            @error('debut')
                                                <span class="text-danger">{{$message}}</span>
                                            @enderror
                                        </div>
                                        <div class="col-4">
                                            <div class="form-group">
                                                <label for="">Date début</label>
                                                <input type="date" class="form-control" name="fin" value="{{old('fin')}}" required>
                                            </div>
                                            @error('fin')
                                                <span class="text-danger">{{$message}}</span>
                                            @enderror
                                        </div>
                                        <div class="col-2">
                                            <button class="btn btn-primary" type="submit" style="margin-top: 2em">Afficher</button>
                                        </div>
                                        <div class="col-1"></div>
                                    </div>
                                </form>

                                <div class="row">
                                    @if(session('resultat'))
                                        @if(count(session('resultat')['programmations']) > 0)

                                            <div class="col-md-12">
                                                <h4 class="col-12 text-center border border-info p-2 mb-2">
                                                    Point des Livraison de la période du {{date_format(date_create(session('resultat')['debut']),'d/m/Y')}}  au {{date_format(date_create(session('resultat')['fin']),'d/m/Y')}}
                                                </h4>
                                                <table  id="example1" class="table table-bordered table-striped table-sm mt-2"  style="font-size: 12px">
                                                    <thead class="text-white text-center bg-gradient-gray-dark">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Code</th>
                                                            <th>Date</th>
                                                            <th>Fournisseur</th>
                                                            <th>Produit</th>
                                                            <th>Camion</th>
                                                            <th>Chauffeur</th>
                                                            <th>Zone</th>
                                                            <th>Qté</th>
                                                            <th>Qté Livré</th>
                                                            <th>Livré le</th>
                                                            <th>BL</th>
                                                            <th>Statut</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="table-body">
                                                    @php($cpt = 0)
                                                    @php($montant = 0)
                                                    @php($regle = 0)
                                                    @foreach(session('resultat')['programmations'] as $key=>$item)
                                                            <tr>
                                                                    <td>{{ $cpt++ }}</td>
                                                                    <td>{{ $item->detailboncommande->boncommande->code }}</td>
                                                                    <td class="text-center">{{ $item->dateprogrammer?date_format(date_create($item->dateprogrammer), 'd/m/Y'):'' }}</td>
                                                                    <td>{{ $item->detailboncommande->boncommande->fournisseur->sigle }}</td>
                                                                    <td>{{ $item->detailboncommande->produit->libelle }}</td>
                                                                    <td>{{ $item->camion->immatriculationTracteur }} ({{ $item->camion->marque->libelle }})</td>
                                                                    <td>{{ $item->chauffeur->nom }} {{ $item->chauffeur->prenom }} ({{ $item->chauffeur->telephone }})</td>
                                                                    <td>{{ $item->zone->libelle }} ({{ $item->zone->departement->libelle }})</td>
                                                                    <td class="text-right">{{ number_format($item->qteprogrammer,2,","," ") }}</td>
                                                                    <td class="text-right">{{ number_format($item->qtelivrer,2,","," ") }}</td>
                                                                    <td class="text-center"><b>{{ $item->datelivrer?date_format(date_create($item->datelivrer), 'd/m/Y'):'' }}</b></td>
                                                                    <td><b>{{ $item->bl }}</b> @if ($item->document)
                                                                        <a  class="btn btn-success text-white btn-xs float-right" href="{{ $item->document?asset('storage/'.$item->document):'' }}" target="_blank"><i class="fa-solid fa-file-pdf"></i></a>
                                                                    @endif</td>
                                                                    <td class="text-center">
                                                                        @if ($item->statut == 'Valider')
                                                                            <span class="badge badge-danger">Non Livré</span>
                                                                        @elseif (($item->statut == 'Livrer') && ($item->qteprogrammer == $item->qtelivrer))
                                                                            @if($item->transfert)
                                                                                <span class="badge badge-info">Transferer</span>
                                                                            @else
                                                                                <span class="badge badge-success">Livré</span>
                                                                            @endif
                                                                        @elseif (($item->statut == 'Livrer') && ($item->qteprogrammer != $item->qtelivrer))
                                                                            @if($item->transfert)
                                                                                <span class="badge badge-info">Transferer</span>
                                                                            @else
                                                                                <span class="badge badge-warning">Partiellement</span>
                                                                            @endif
                                                                        @endif
                                                                    </td>
                                                            </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="col-12 text-center border border-info p-2">
                                                Aucun information trouvée pour votre requête.
                                            </div>
                                        @endif

                                    @endif
                                </div>
                                <div class="card-footer text-center no-print">
                                    @if(session('resultat'))
                                        @if(count(session('resultat')['programmations']) > 0)
                                            <button class="btn btn-success" onclick="window.print()"><i class="fa fa-print"></i> Imprimer</button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
