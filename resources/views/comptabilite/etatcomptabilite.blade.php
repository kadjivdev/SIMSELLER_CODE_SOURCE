@extends('layouts.app')
@section('content')

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <h1 class="pb-3">VENTE COMPTABILISER </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('welcome') }}">Accueil</a></li>
                            <li class="breadcrumb-item active">Etat des comptables</li>
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
                                <form method="post" id="form_bc" action="{{route('ventes.postexport')}}">
                                    @csrf
                                    <div class="row no-print" >
                                        <div class="col-1"></div>
                                        <div class="col-3">
                                            <div class="form-group mt-3">
                                                <div class="custom-control custom-radio">
                                                <input class="custom-control-input custom-control-input-success" type="radio" id="customRadio4" name="filtre" checked="" value="on">
                                                <label for="customRadio4" class="custom-control-label">Filtrer par date de traitement</label>
                                                </div>
                                                <div class="custom-control custom-radio">
                                                <input class="custom-control-input custom-control-input-success custom-control-input-outline" type="radio" id="customRadio5" name="filtre" value="off" >
                                                <label for="customRadio5" class="custom-control-label">Filtrer par date de création</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label for="">Fournisseur</label>
                                                <select  id="client" class="form-control form-control-sm select2"  name="fournisseur">
                                                    <option class="" value="" selected>Tous</option>
                                                    @foreach($fournisseurs as $frs)
                                                        <option value="{{$frs->sigle}}" {{old('fournisseur')==$frs->id?'selected':''}}>{{$frs->raisonSociale}}</option>
                                                    @endforeach
                                                </select>

                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label for="">Date début</label>
                                                <input type="date" class="form-control" name="debut" value="{{old('debut')}}" required>
                                            </div>
                                            @error('debut')
                                                <span class="text-danger">{{$message}}</span>
                                            @enderror
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label for="">Date Fin</label>
                                                <input type="date" class="form-control" name="fin" value="{{old('fin')}}" required>
                                            </div>
                                            @error('fin')
                                                <span class="text-danger">{{$message}}</span>
                                            @enderror
                                        </div>
                                        <div class="col-2">
                                            <button class="btn btn-primary" type="submit" style="margin-top: 2em">Afficher</button>
                                        </div>
                                        
                                    </div>
                                </form>

                                <div class="row">
                                    @if(session('resultat'))
                                        @if(count(session('resultat')['comptabilisers']) > 0)

                                            <div class="col-md-12">
                                                <h4 class="col-12 text-center border border-info p-2 mb-2">
                                                    Point des vente Traiter et à comptabiliser de la période du {{date_format(date_create(session('resultat')['debut']),'d/m/Y')}}  au {{date_format(date_create(session('resultat')['fin']),'d/m/Y')}}
                                                </h4>
                                              
                                                <table class="table table-bordered table-striped table-sm mt-2"  style="font-size: 12px">
                                                    <thead class="text-white text-center bg-gradient-gray-dark">
                                                    <tr>
                                                        <th>Heure système</th>
                                                        <th>Date système</th>
                                                        <th>Code vente</th>
                                                        <th>Date vente</th>
                                                        <th>Client</th>
                                                        <th>IFU</th>
                                                        <th>Client Filleul</th>
                                                        <th>IFU Filleul</th>
                                                        <th>Date achat</th>
                                                        <th>Produit</th>
                                                        <th>Quantité</th>
                                                        <th>PVR</th>
                                                        <th>Prix TTC</th>
                                                        <th>Prix HT</th>
                                                        <th>Prix 1.18</th>
                                                        <th>Net HT</th>
                                                        <th>TVA</th>
                                                        <th>AIB</th>
                                                        <th>TTC</th>
                                                        <th>FRS</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    
                                                    @foreach(session('resultat')['comptabilisers'] as $key=>$item)
                                                        <tr>
                                                            <td>{{$item->heureSysteme}}</td>
                                                            <td>{{date_format(date_create($item->dateSysteme),'d/m/Y')}}</td>
                                                            <td>{{$item->code}}</td>
                                                            <td>{{date_format(date_create($item->dateVente),'d/m/Y')}}</td>
                                                            <td>{{$item->clients}}</td>
                                                            <td>{{$item->ifu}}</td>
                                                            <td>{{$item->clientFilleuls}}</td>
                                                            <td>{{$item->clientFilleulsifu}}</td>
                                                            <td>{{date_format(date_create($item->dateAchat),'d/m/Y')}}</td>
                                                            <td>{{$item->produit}}</td>
                                                            <td>{{$item->qte}}</td>
                                                            <td>{{$item->pvr}}</td>
                                                            <td>{{$item->prixTTC}}</td>
                                                            <td>{{$item->PrixHT}}</td>
                                                            <td>{{$item->PrixBruite}}</td>
                                                            <td>{{$item->NetHT}}</td>
                                                            <td>{{$item->TVA}}</td>
                                                            <td>{{$item->AIB}}</td>
                                                            <td>{{$item->TTC}}</td>
                                                            <td>{{$item->FRS}}</td>
                                                           
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
                                        @if(count(session('resultat')['comptabilisers']) > 0)
                                            <a href="{{ route('ventes.export',['debut'=>session('resultat')['debut'],'fin'=>session('resultat')['fin'] , 'filtre'=>session('resultat')['filtre']]) }}" class="btn btn-success"><i class="fa fa-file-excel"></i> Exporter la liste</a>
                                           
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
