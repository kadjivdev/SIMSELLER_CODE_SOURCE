<?php

namespace App\Http\Controllers;

use App\Models\BonCommande;
use App\Models\CommandeClient;
use App\Models\DetailBonCommande;
use App\Models\Programmation;
use App\Models\Vendu;
use App\Models\Vente;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $boncommandesP = BonCommande::where('statut', 'Préparation')->count();
        $boncommandesV = BonCommande::where('statut', 'Valider')->count();
        $programmationsV = Programmation::where('statut', 'Valider')->count();
        $cdes = BonCommande::where('statut', 'Valider')->get();
        $progs = Programmation::whereNotNull('imprimer')->count();
        $livs = Programmation::whereNotNull('qtelivrer')->get();
        $sansRecu = 0;
        $nbrLiv = 0;
        $qteLiv = 0;
        foreach ($cdes as $cde) {
            if (!$cde->recu)
                $sansRecu++;
        }
        foreach ($livs as $liv) {
            $qteVendu = Vendu::where('programmation_id', $liv->id)->sum('qteVendu');
            $stockDispo = $liv->qtelivrer - $qteVendu;
            if ($stockDispo > 0) {
                $qteLiv += $stockDispo;
                $nbrLiv++;
            }
        }
        //Produit bon commande non programmé
        //$boncommandes = BonCommande::where('statut', 'Valider')->pluck('id');
        $produitNP = $progs; //DetailBonCommande::whereIn('bon_commande_id', $boncommandes)->whereNotIn('id', $programmations)->count();
        $now = Carbon::now();
        $vente = Vente::where('statut', 'Vendue')->whereBetween('date', [$now->startOfWeek()->format('Y-m-d'), $now->endOfWeek()->format('Y-m-d')])->sum('montant');
        $cde = CommandeClient::where('statut', 'Valider')->whereBetween('dateBon', [$now->startOfWeek()->format('Y-m-d'), $now->endOfWeek()->format('Y-m-d')])->count();
        $impayer = 0;
        $client = 0;
        $ventes = Vente::where('statut', 'Vendue')->where('type_vente_id', 2)->orderByDesc('code')->get();
        foreach ($ventes as $vte) {
            if (($vte->montant - $vte->reglements()->sum('montant')) <> 0) {
                $client++;
                $impayer += $vte->montant - $vte->reglements()->sum('montant');
            }
        }

        if (!in_array('ADMINISTRATEUR', array_column(auth()->user()->roles->toArray(), 'libelle')) || !in_array('SUPERVISEUR', array_column(auth()->user()->roles->toArray(), 'libelle')))
            return view('representant', compact('boncommandesP', 'boncommandesV', 'programmationsV', 'produitNP', 'sansRecu', 'nbrLiv', 'qteLiv', 'vente', 'cde', 'client', 'impayer'));
        else        
            return view('dashboard', compact('boncommandesP', 'boncommandesV', 'programmationsV', 'produitNP', 'sansRecu', 'nbrLiv', 'qteLiv', 'vente', 'cde', 'client', 'impayer'));
    }
}
