<?php

namespace App\Http\Controllers;

use App\Exports\ComptabiliteExport;
use App\Exports\ComptabiliteReExport;
use App\Mail\NotificateurProgrammationMail;
use App\Mail\NotificationAskUpdateVente;
use App\Mail\NotificationVenteMail;
use App\Models\EcheanceCredit;
use App\Models\User;
use App\tools\CommandeClientTools;
use Exception;
use App\Models\Zone;
use App\Models\Vente;
use App\Models\Client;
use App\Models\Parametre;
use App\Models\TypeCommande;
use Illuminate\Http\Request;
use App\Models\CommandeClient;
use App\Models\filleuil;
use App\Models\Fournisseur;
use App\tools\ControlesTools;
use Illuminate\Mail\Transport\Transport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
//use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel;

use function PHPSTORM_META\map;

class VenteController extends Controller
{
   public $resultat;
   
   public function __construct()   
   {
       $this->middleware('vendeur')->only(['create','store', 'update', 'delete']);
   }
    public function index(Request $request)
    {
        $roles = Auth::user()->roles()->pluck('id')->toArray();
            $commandeclients = CommandeClient::whereIn('statut', ['Préparation', 'Vendue','Validée','Livraison partielle','Livrée']);
        if ($request->debut && $request->fin) 
            $commandeclients =$commandeclients->WhereBetween('dateBon', [$request->debut, $request->fin])->pluck('id');
        else  
            $commandeclients =$commandeclients->pluck('id');
            
       
        if(in_array(1,$roles) || in_array(2,$roles) || in_array(5,$roles) || in_array(8,$roles) || in_array(9,$roles) || in_array(10,$roles) || in_array(11,$roles))
            $ventes = Vente::whereIn('commande_client_id', $commandeclients)->where('statut','<>','En attente de modification')->orderByDesc('code')->paginate(1000);
        elseif(in_array(3,$roles))
            $ventes = Vente::whereIn('commande_client_id', $commandeclients)->where('statut','<>','Contrôller')->where('statut','<>','En attente de modification')->where('users',Auth::user()->id)->orderByDesc('date')->paginate(1000);

        return view('ventes.index', compact('ventes'));
    } 

    public function indexCreate(Request $request)
    {
        $roles = Auth::user()->roles()->pluck('id')->toArray();
        if ($request->debut && $request->fin){
            $ventes = Vente::whereBetween('ventes.created_at', [$request->debut, $request->fin])->where('statut','<>','En attente de modification')->orderBy('date', 'ASC')->paginate(1000);
        }else{
            $date = date('Y-m-d');
            $ventes = Vente::whereDate('created_at', $date)->orderBy('created_at', 'ASC')->paginate(1000);
        }
         
        return view('ventes.indexCreate', compact('ventes'));
    } 

    public function indexControlle(Request $request)
    {
        $roles = Auth::user()->roles()->pluck('id')->toArray();
       
       
        if(in_array(1,$roles) || in_array(2,$roles) || in_array(8,$roles)|| in_array(9,$roles)|| in_array(10,$roles) || in_array(11,$roles))
            $ventes = Vente::where('statut','=','Contrôller')->orderBy('date', 'ASC')->paginate(1000);
        elseif(in_array(3,$roles))
            $ventes = Vente::where('statut','=','Contrôller')->where('users',Auth::user()->id)->orderByDesc('date')->paginate(1000);

        return view('ventes.indexControlle', compact('ventes'));
    } 

    public function detailVente(Vente $vente){
        $commandeclients = CommandeClient::whereIn('statut', ['Préparation', 'Vendue','Validée','Livraison partielle','Livrée'])->pluck('id');
        $ventes = Vente::whereIn('commande_client_id', $commandeclients)
        ->where('ventes.statut','<>','Contrôller')
        ->where('ventes.statut','<>','En attente de modification')
        //->where('ventes.users',Auth::user()->id)
        ->where('ventes.id',$vente->id)
        ->join('vendus','vendus.vente_id','=','ventes.id')
        ->join('programmations','programmations.id','=','vendus.programmation_id')
        ->join('detail_bon_commandes','detail_bon_commandes.id','=','programmations.detail_bon_commande_id')
        ->join('produits','produits.id','=','detail_bon_commandes.produit_id')
        ->join('bon_commandes','bon_commandes.id','=','detail_bon_commandes.bon_commande_id')
        ->join('camions','camions.id','=','programmations.camion_id')
        ->join('chauffeurs','chauffeurs.id','=','programmations.chauffeur_id')
        ->select('ventes.code as vente','programmations.code','bon_commandes.code as codeBC','programmations.bl','camions.immatriculationTracteur','chauffeurs.nom','chauffeurs.prenom','vendus.qteVendu','ventes.destination','produits.libelle',)
        ->orderByDesc('date')->get();
        return response()->json($ventes);
    }


    public function create(Request $request)
    {
        $typeVente = [];
        $user = User::find(Auth::user()->id);
        $repre = $user->representant;
        
        $zones = $repre->zones;
        if ( $repre->nom =='DIRECTION') {
            $zones = Zone::all();
        }
        
        if ($request->statuts) {
            if ($request->statuts == 1) {
                $clients = Client::all();
                //$zones = Zone::all();
                $typecommandeclient = TypeCommande::where('libelle', 'COMPTANT')->first();
                $commandeclients = CommandeClient::whereIn('statut', ['Non livrée', 'Livraison partielle'])->get();
                $req = $request->statuts;
            }
            elseif ($request->statuts == 2) {
                $clients = Client::all();                
                //$zones = Zone::all();
                $typecommandeclient = TypeCommande::where('libelle', 'COMPTANT')->first();
                $commandeclients = CommandeClient::where('statut', 'Validée')->orWhere('statut', 'Livraison partielle')->whereNull('byvente')->where('type_commande_id',2)->get();
                $req = $request->statuts;
            }
        }
        else {
            $clients = Client::all();
            //$zones = Zone::all();
            $typecommandeclient = TypeCommande::where('libelle', 'COMPTANT')->first();
            $commandeclients = CommandeClient::whereIn('statut', ['Non livrée', 'Livraison partielle'])->get();
            $req = 1;
        }

        $redirectto = $request->redirectto;
        $vente = NULL;
        return view('ventes.create', compact('vente', 'typecommandeclient', 'clients', 'commandeclients', 'zones', 'redirectto', 'req','typeVente'));
    }



    public function store(Request $request)
    {
       //  try {
            $req = NULL;
            if ($request->statuts == 1) {
                //dd($request->statuts);
                if($request->type_vente_id == 1){
                    $validator = Validator::make($request->all(), [
                        'date' => ['required','before_or_equal:'.date('Y-m-d')],
                        'client_id' => ['required'],
                        'zone_id' => ['required'],
                        'type_vente_id' => ['required'],
                        'transport' => ['required'],
                        'ctl_payeur'=>['required'],
                        //'nomPrenom'=>['required_if:clt_payeur,==,0'],
                        //'telephone'=>['required_if:clt_payeur,==,0','integer']
                    ]);
                }
                else{
                    $validator = Validator::make($request->all(), [
                        'date' => ['required','before_or_equal:'.date('Y-m-d')],
                        'client_id' => ['required'],
                        'zone_id' => ['required'],
                        'type_vente_id' => ['required'],
                        'echeance'=>['required','after:'.date('Y-m-d')],
                        'transport' => ['required'],
                        'ctl_payeur'=>['required'],
                        //'nomPrenom'=>['required_if:clt_payeur,==,0'],
                        //'telephone'=>['required_if:clt_payeur,==,0','integer']
                    ]);
                }

                $req = $request->statut;
                if ($validator->fails()) {
                    return redirect()->route('ventes.create', ['statuts'=>$req])->withErrors($validator->errors())->withInput();
                }

                $format = env('FORMAT_COMMANDE_CLIENT');
                $parametre = Parametre::where('id', env('COMMANDE_CLIENT'))->first();
                $code = $format . str_pad($parametre->valeur, 7, "0", STR_PAD_LEFT);

                $commandeclients = CommandeClient::create([
                    'code' => $code,
                    'dateBon' => $request->date,
                    'statut' => "Préparation",
                    'type_commande_id' => $request->type_vente_id,
                    'client_id' => $request->client_id,
                    'zone_id' => $request->zone_id,
                    'users' => Auth::user()->id,
                    'byvente'=>1
                ]);

                if ($commandeclients) {

                    $valeur = $parametre->valeur;

                    $valeur = $valeur + 1;

                    $parametres = Parametre::find(env('COMMANDE_CLIENT'));

                    $parametre = $parametres->update([
                        'valeur' => $valeur,
                    ]);


                    if ($parametre) {
                        $format = env('FORMAT_VENTE_D');
                        $parametre = Parametre::where('id', env('VENTE'))->first();
                        $code = $format . str_pad($parametre->valeur, 7, "0", STR_PAD_LEFT);
                        if($request->ctl_payeur == 0){
                            $filleuls = json_encode([
                                'nomPrenom'=>$request->nomPrenom,
                                'telephone'=>$request->telephone,
                                'ifu'=>$request->ifu
                            ]);
                        }
                        else{
                            $filleuls = null;
                        }

                        $ventes = Vente::create([
                            'code' => $code,
                            'date' => $request->date,
                            'statut' => "Préparation",
                            'commande_client_id' => $commandeclients->id,
                            'users' => Auth::user()->id,
                            'type_vente_id'=>$request->type_vente_id,
                            'transport'=>$request->transport,
                            'ctl_payeur'=>$request->ctl_payeur,
                            'filleuls'=>$filleuls
                        ]);

                        if ($ventes) {

                            $valeur = $parametre->valeur;

                            $valeur = $valeur + 1;

                            $parametres = Parametre::find(env('VENTE'));

                            $parametres = $parametres->update([
                                'valeur' => $valeur,
                            ]);

                            if($request->type_vente_id == 2){
                                EcheanceCredit::create([
                                    'date'=>$request->echeance,
                                    'statut'=>0,
                                    'vente_id'=>$ventes->id,
                                    'user_id'=>auth()->user()->id
                                ]);
                            }

                            if ($parametres) {
                                Session()->flash('message', 'Vente enregistrée avec succès!');
                                return redirect()->route('vendus.create', ['vente' => $ventes->id]);
                            }
                        }
                    }
                }
            }
            elseif ($request->statuts == 2) {
                if($request->type_vente_id == 1){
                    $validator = Validator::make($request->all(), [
                        'date' => ['required', 'before_or_equal:'.date('Y-m-d')],
                        'commande_client_id' => ['required'],
                        'zone_id' => ['required'],
                        'type_vente_id' => ['required'],
                    ]);
                }
                else{
                    $validator = Validator::make($request->all(), [
                        'date' => ['required', 'before_or_equal:'.date('Y-m-d')],
                        'commande_client_id' => ['required'],
                        'zone_id' => ['required'],
                        'type_vente_id' => ['required'],
                        'echeance'=>['required','after:'.date('Y-m-d')],
                        'transport' => ['required'],
                    ]);
                }

                $req = $request->statuts;
                if ($validator->fails()) {
                    return redirect()->route('ventes.create', ['statuts'=>$req])->withErrors($validator->errors())->withInput();
                }

                $format = env('FORMAT_VENTE_C');
                $parametre = Parametre::where('id', env('VENTE'))->first();
                $code = $format . str_pad($parametre->valeur, 7, "0", STR_PAD_LEFT);

                $ventes = Vente::create([
                    'code' => $code,
                    'date' => $request->date,
                    'statut' => "Préparation",
                    'commande_client_id' => $request->commande_client_id,
                    'users' => Auth::user()->id,
                    'type_vente_id' => $request->type_vente_id,
                    'transport'=>$request->transport,
                    'ctl_payeur'=>$request->ctl_payeur
                ]);

                if ($ventes) {

                    $valeur = $parametre->valeur;

                    $valeur = $valeur + 1;

                    $parametres = Parametre::find(env('VENTE'));

                    $parametres = $parametres->update([
                        'valeur' => $valeur,
                    ]);
                    if($request->type_vente_id == 2){

                        EcheanceCredit::create([
                            'date'=> $request->echeance,
                            'statut'=>0,
                            'vente_id'=> $ventes->id,
                            'user_id'=> auth()->user()->id
                        ]);

                    }

                    if ($parametres) {
                        Session()->flash('message', 'Vente enregistrée avec succès!');
                        return redirect()->route('vendus.create', ['vente' => $ventes->id]);
                    }
                }
            }



       /*  }
        catch (Exception $e) {
            if (env('APP_DEBUG') == TRUE) {
                return $e;
            }
            else {
                Session()->flash('error', 'Opps! Enregistrement échoué. Veuillez contacter l\'administrateur système!');
                return redirect()->route('vendus.index');
            }
        } */

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Vente  $vente
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function show(Vente $vente)
    {
        return view('ventes.show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Vente  $vente
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory
     */
    public function edit(Request $request, Vente $vente)
    {
                $user = User::find(Auth::user()->id);
                $repre = $user->representant;
                $zones = $repre->zones;
            if ( $repre->nom =='DIRECTION') {
                    $zones = Zone::all();
                }
            if ($vente->commandeclient->type_commande_id == 1) {
                $clients = Client::all();               
                $typecommandeclient = TypeCommande::where('libelle', 'COMPTANT')->first();
                $commandeclients = CommandeClient::whereIn('statut', ['Non livrée', 'Livraison partielle'])->get();
                $req = $vente->commandeclient->type_commande_id ;
            }
            else{
                $clients = Client::all();                
                $typecommandeclient = TypeCommande::where('libelle', 'COMPTANT')->first();
                $commandeclients = CommandeClient::where('statut', 'Non livrée')->orWhere('statut', 'Livraison partielle')->where('type_commande_id',2)->get();
                $req = $vente->commandeclient->type_commande_id;
            }


        $redirectto = $request->redirectto;
        return view('ventes.edit', compact('vente', 'typecommandeclient', 'clients', 'commandeclients', 'zones', 'redirectto', 'req'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Vente  $vente
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Vente $vente)
    {
        try {
            $req = NULL;
            if ($vente->commandeclient->type_commande_id == 1) {

                $validator = Validator::make($request->all(), [
                    'date' => ['required','before_or_equal:'.date('Y-m-d')],
                    'client_id' => ['required'],
                    'zone_id' => ['required'],
                    'type_vente_id' => ['required'],
                    'transport' => ['required'],
                ]);
                $req = $vente->commandeclient->type_commande_id;
                if ($validator->fails()) {
                    return redirect()->route('ventes.edit',['vente'=>$vente->id])->withErrors($validator->errors())->withInput();
                }

                        $format = env('FORMAT_VENTE_D');
                        $parametre = Parametre::where('id', env('VENTE'))->first();
                        $code = $format . str_pad($parametre->valeur, 4, "0", STR_PAD_LEFT);

                        $vente->commandeclient()->update([
                            'type_commande_id'=>$request->type_vente_id,
                            'client_id'=>$request->client_id,
                            'zone_id'=>$request->zone_id
                        ]);
                        $vente->update([
                            'date' => $request->date,
                        ]);

                        Session()->flash('message', 'Vous avez modifier avec succès la vente. Faite la relecture du détails');
                        return redirect()->route('vendus.create', ['vente' => $vente->id]);

            }
            elseif ($vente->commandeclient->type_commande_id == 2) {

                $validator = Validator::make($request->all(), [
                    'date' => ['required'],
                    'commande_client_id' => ['required'],
                    'zone_id' => ['required'],
                    'type_vente_id' => ['required'],
                    'transport' => ['required'],
                ]);
                $req = $vente->commandeclient->type_commande_id;
                if ($validator->fails()) {
                    return redirect()->route('ventes.create', $req)->withErrors($validator->errors())->withInput();
                }

                $vente->update([
                    'date' => $request->date,
                    'commande_client_id' => $request->commande_client_id,
                    'users' => Auth::user()->id,
                    'type_vente_id'=>$request->type_vente_id,
                    'transport' => $request->transport,
                    'ctl_payeur' => $request->ctl_payeur
                ]);

                if($request->type_vente_id == 1){
                    $vente->echeances()->delete();
                }

                Session()->flash('message', 'Vous avez modifier avec succès la vente. Faite la relecture du détails');
                return redirect()->route('vendus.create', ['vente' => $vente->id]);
            }

        }
        catch (Exception $e) {
            if (env('APP_DEBUG') == TRUE) {
                return $e;
            }
            else {
                
                Session()->flash('error', 'Opps! Enregistrement échoué. Veuillez contacter l\'administrateur système!');
                return redirect()->route('vendus.index');
            }
        }
    }
    
    public function delete(Vente $vente)
    {
        if (Auth::user()->id == $vente->user->id) {
            return view('ventes.delete', compact('vente'));
        }else {
            Session()->flash('error', 'Vos n\'êtes eligible à une suppression.');
            return redirect()->route('ventes.index');
         }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Vente  $vente
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Vente $vente){
        ControlesTools::generateLog($vente,'Vente','Suppression ligne');
     
        if ( $vente->vendus) {
         $vente->vendus()->delete();
         $vente->commandeclient()->delete();
         $vente->delete();
        }else {
            $vente->commandeclient()->delete();
            $vente->delete();
       }
        Session()->flash('message', 'Vente supprimer avec succès.');
        return redirect()->route('ventes.index', ['message' => $vente]);
    }
    public function invalider(Vente $vente){
        return view('ventes.invalider', compact('vente'));
    }
    public function posteInvalider(Vente $vente){
        $vente->update(['statut'=>'Préparation']);
        CommandeClientTools::changeStatutCommande($vente->commandeclient);
        return redirect()->route('ventes.index')->with('message', 'Votre vente est passé en préparation.');
    }

    public function validationVente(Vente $vente){
        if($vente->statut == "Vendue"){
            Session()->flash('message', 'Vous avez déjà valider cette vente n° '.$vente->code);
            return redirect()->route('ventes.index',['vente'=>$vente->id]);
        }
        if($vente->vendus()->sum('qteVendu') && $vente->vendus()->sum('qteVendu') == $vente->qteTotal){
            $vente->update(['statut'=>'Vendue']);
            CommandeClientTools::changeStatutCommande($vente->commandeclient);
            $venteAttentes = DB::select("
                SELECT date,COUNT(*) AS nombre
                FROM ventes
                WHERE statut = 'Vendue'
                GROUP BY date
            ");
            $desMail = User::find(env('GESTIONNAIRE_VENTE'));
            $copieMail = User::find(env('COPIE_GESTIONNAIRE_VENTE'));
            $message = "<p>Nous vous notifions une nouvelle vente effectuée par <b>".$vente->user->name."</b>. Merci de vous connecter pour traiter.</p>";
            $mail = new NotificationVenteMail(['email'=>$desMail->email,'nom'=>$desMail->nom],'Vente n° '.$vente->code.' du '.date_format(date_create($vente->date),'d/m/Y'),$message,$vente,$venteAttentes,$copieMail->email);
            Mail::send($mail);
            return redirect()->route('ventes.index')->with('message', 'Félicitation! Votre vente a été enregistrée');
        }
        else
            abort(403);

    }
    public function initVente(Vente $vente){
        $vente->update([
            'statut'=>'Préparation',
            'montant'=>null,
            'qteTotal'=>null,
            'pu'=>null,
            'produit_id'=>null,
            'remise'=>null,
            'destination'=>null
        ]);
        $vente->vendus()->delete();
        return redirect()->route('vendus.create',['vente'=>$vente->id])->with('msgSuppression','Vente initialisée.');
    }
    public function aComptabiliser(Vente $vente){
        try {
            $vente->date_envoie_commercial = date('Y-m-d');
            $vente->user_envoie_commercial = Auth()->user()->id;
            $vente->update();
           return redirect()->back();
            
         } catch (\Throwable $th) {
            //throw $th;
        } 
    }

    public function cltpayeur( Client $client){
        $filleuls = $client->filleulFisc;
        $clientParrain = [];
        foreach ($filleuls as $filleul){
            $clt = Client::find($filleul);
            if($clt)
                $clientParrain[] = $clt;
        }
        return response($clientParrain);
    }
    public function showVente(Vente $vente){
        return response()->json($vente);
    }
    public function demandeVente(Request $request ){
        try {
            $vente = Vente::find($request->id);

            $objetDemande =  json_encode([
                'dateDemande'=>date("Y-m-d H:i:s"),
                'user_id'=>Auth()->user()->id,

                'qteOld'=>$vente->qteOld,
                'prixUnitaireOld'=>$vente->pu,
                'transportOld'=>$vente->transport,

                'observation'=>$request->observation,

                'qteNew'=>$request->qteNew,
                'prixUnitaireNew'=>$request->PrixUnitaireNew,
                'transportNew'=>$request->PrixTransportNew,
            ]);

            if ($vente->ask_history) {
                $ask_history =json_decode($vente->ask_history); 
                $ask_history[] = $objetDemande;
                $ask_history = json_encode($ask_history);
                $vente->update(['ask_history'=>$ask_history,'statut'=>'En attente de modification']);
            }else{
                $ask_history = [$objetDemande];
                $ask_history = json_encode($ask_history);
                $vente->update(['ask_history'=>$ask_history,'statut'=>'En attente de modification']);
            }

            $desMail = User::find(env('GESTIONNAIRE_ID'));
            $copieMail = User::find(env('COPIE_GESTIONNAIRE_VENTE'));
            $message = "
                <p>Nous vous notifions une nouvelle demande de modification vente effectuée par <b>".Auth()->user()->name."</b> <br>.
                <b>Ci-joint l'observation de modification<b>:<br> <i>".$request->observation."</i>
                </p>";
                $lienAction =  route('ventes.askUpdate');
            $mail = new NotificationAskUpdateVente(['email'=>$desMail->email,'nom'=>$desMail->nom],'Demande de mofication Vente n° '.$vente->code.' du '.date_format(date_create($vente->date),'d/m/Y'),$message,$vente,$copieMail->email,$lienAction);
            Mail::send($mail); 
            return back();
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
    public function askUpdate(){
        $ventes = Vente::where('statut','En attente de modification')->where('users',Auth::user()->id)->orderByDesc('date')->get();
        return view('ventes.askUpadate',compact('ventes'));
    }
    public function envoieComptabilite(Request $request){
        $ventes = explode(",",$request->ventes);

        foreach ($ventes as  $vente) {

            $vente = Vente::find($vente);
            $vente->date_envoie_commercial = date('Y-m-d');
            $vente->user_envoie_commercial = Auth()->user()->id;
            $vente->update();

            //Mise à jour du compte Client.
            $client = $vente->commandeclient->client;
            $client->debit = $client->debit - $vente->montant;
            $client->update();

            // Mise à jour du Solde du client 
             $client = $vente->commandeclient->client;
             $compteClient = $client->compteClients[0];
             $compteClient->solde = $client->credit + $client->debit;
             $compteClient->update();


        }
        return back();

    }
    public function venteAEnvoyerComptabiliser(){
        $AEnvoyers = Vente::all()->whereIn('statut', ['Vendue','Contrôller','Soldé'])->where('date_envoie_commercial', NULL);
        return view('comptabilite.listesVenteAEnvoyer',compact('AEnvoyers'));
    }

    public function getVenteAComptabiliser(){
        
        return view('comptabilite.listesVente');
    }

    public function postVenteAComptabiliser(Request $request){
        $AComptabilisers =  Vente::where('date_envoie_commercial','<>', NULL)
        ->where('date_traitement', NULL)->whereIn('ventes.statut', ['Vendue','Contrôller','Soldé'])
        ->whereDate('ventes.created_at','>=',$request->debut)
        ->whereDate('ventes.created_at','<=',$request->fin)
        ->join('vendus', 'ventes.id', '=', 'vendus.vente_id')
        ->join('programmations', 'programmations.id', '=', 'vendus.programmation_id')
        ->join('detail_bon_commandes', 'detail_bon_commandes.id', '=', 'programmations.detail_bon_commande_id')
        ->join('bon_commandes', 'bon_commandes.id', '=', 'detail_bon_commandes.bon_commande_id')
        ->join('fournisseurs', 'fournisseurs.id', '=', 'bon_commandes.fournisseur_id')
        ->select('ventes.*','fournisseurs.sigle')->where('fournisseurs.id', '<>',4)
        ->orderBy('date', 'DESC')
        ->get();

        $AComptabilisersAdjeOla = Vente::where('date_envoie_commercial','<>', NULL)
        ->where('date_traitement', NULL)->whereIn('ventes.statut', ['Vendue','Contrôller','Soldé'])
        ->whereDate('ventes.created_at','>=',$request->debut)
        ->whereDate('ventes.created_at','<=',$request->fin)
        ->join('vendus', 'ventes.id', '=', 'vendus.vente_id')
        ->join('programmations', 'programmations.id', '=', 'vendus.programmation_id')
        ->join('detail_bon_commandes', 'detail_bon_commandes.id', '=', 'programmations.detail_bon_commande_id')
        ->join('bon_commandes', 'bon_commandes.id', '=', 'detail_bon_commandes.bon_commande_id')
        ->join('fournisseurs', 'fournisseurs.id', '=', 'bon_commandes.fournisseur_id')
        ->select('ventes.*','fournisseurs.sigle')->where('fournisseurs.id', 4)
        ->orderBy('date', 'DESC')
        ->get();      
       
        session(['debut_compta' => $request->debut]);
        session(['fin_compta' => $request->fin]);

        return redirect()->route('ventes.venteAComptabiliser')->withInput()->with('resultat',['AComptabilisers'=>$AComptabilisers,'AComptabilisersAdjeOla'=>$AComptabilisersAdjeOla, 'debut'=>$request->debut, 'fin'=>$request->fin]);
    }

    public function ventATraiter(Vente $vente){
       $payeur = $vente->filleule;
       $client = $vente->commandeclient->client;
       return view('comptabilite.traitementVente',compact('vente','client','payeur'));
    }

    public function traiterVente(Request $request,Vente $vente){
        try {

            $vente->taux_aib = $request->taux_aib;
            $vente->taux_tva = $request->taux_tva;
            $vente->prix_Usine = $request->prix_Usine;
            $vente->prix_TTC = $request->prix_TTC;
            $vente->marge = $request->marge;
            $vente->date_traitement = date('Y-m-d');
            $vente->user_traitement = Auth()->user()->id;
            $vente->update();
            
            return redirect()->route('ventes.getPostVenteAComptabiliser',[
                'debut'=>session('debut_compta'),
                'fin'=>session('fin_compta')
               ]);
           
        } catch (\Throwable $th){
            //throw $th;
        }
     }
     
    public function listeDesTraitementPeriode(){
        return view('comptabilite.listesDesTraitementPeriode');
    }

    public function postListeDesTraitementPeriode(Request $request){
        //Prévoir le validator
        $request->validate([
           'debut'=>['required'],
           'fin'=>['required']
        ]);
        $ventes = Vente::where('ventes.statut', 'Contrôller')
        ->whereBetween('date_traitement', [$request->debut, $request->fin])->orderByDesc('ventes.code')        
        ->join('commande_clients', 'ventes.commande_client_id', '=', 'commande_clients.id')
        ->join('clients', 'commande_clients.client_id', '=', 'clients.id')
        ->join('vendus', 'ventes.id', '=', 'vendus.vente_id')
        ->join('programmations', 'programmations.id', '=', 'vendus.programmation_id')
        ->join('detail_bon_commandes', 'detail_bon_commandes.id', '=', 'programmations.detail_bon_commande_id')
        ->join('bon_commandes', 'bon_commandes.id', '=', 'detail_bon_commandes.bon_commande_id')
        ->join('fournisseurs', 'fournisseurs.id', '=', 'bon_commandes.fournisseur_id')
        ->select('ventes.*','clients.*','bon_commandes.code as codeBon','bon_commandes.dateBon','fournisseurs.sigle as fournisseur')
        ->with('produit','payeur')
        ->get();
        

      //  $ventes = Vente::where('statut', 'Contrôller')->whereBetween('date_traitement',[$request->debut, $request->fin])->orderByDesc('code')->get();
        return redirect()->route('ventes.listeDesTraitementPeriode')->withInput()->with('resultat',['ventes'=>$ventes, 'debut'=>$request->debut, 'fin'=>$request->fin]);
    }
    public function filleuilfictive(Request $request){
        $validator = Validator::make($request->all(),[
            'name'=>['required'],
            'ifu'=>['required'],
        ]);
        if ($validator->fails()) {
            Session()->flash('message', 'Renseigner les champ s\'il vous plait.');
            return back();
        }
        $filleul = new filleuil;
        $filleul->name = $request->name;
        $filleul->ifu = $request->ifu;
        $filleul->save();
        return back();
    }
    
     public function testMail(){
        $mail = new NotificateurProgrammationMail('test',['email'=>'to@exemple.com','nom'=>'KANHONOU Arnauld'],'Nouvelle commande','Bonjour');
        Mail::send($mail);
    }
    public function viewVenteTraiter(){
        $fournisseurs = Fournisseur::all();
        return view('comptabilite.etatcomptabilite',compact('fournisseurs'));
    }
    public function viewVenteComptabiliser(){
        $fournisseurs = Fournisseur::all();
        return view('comptabilite.etatDejacomptabilite',compact('fournisseurs'));
    }
    public function postexport(Request $request)
    {
            $comptabiliser =[];
            if($request->filtre == 'on'){
                session(['filtre'=>$request->filtre]);
                if ($request->fournisseur){
                    $comptabiliser =  DB::select(
                        "SELECT  *           
                            FROM `export_comptabilite`  
                                WHERE  `export_comptabilite`.`date_traitement` BETWEEN ? AND ?
                                AND  `export_comptabilite`.`FRS` = ?
                                AND  `export_comptabilite`.`date_comptabilisation` IS NULL
                                ORDER BY `export_comptabilite`.`date_traitement` DESC;
                     ",[$request->debut, $request->fin, $request->fournisseur]);
                }else{
                        $comptabiliser =  DB::select(
                        "SELECT  *           
                            FROM `export_comptabilite`  
                                WHERE  `export_comptabilite`.`date_traitement` BETWEEN ? AND ?
                                AND  `export_comptabilite`.`date_comptabilisation` IS NULL
                                ORDER BY `export_comptabilite`.`date_traitement`DESC;

                    ",[$request->debut, $request->fin]);
                }
               
            }else {
                session(['filtre'=>$request->filtre]);
                if ($request->fournisseur){
                    $comptabiliser =  DB::select(
                        "SELECT  *           
                            FROM `export_comptabilite`  
                                WHERE  `export_comptabilite`.`dateCreate` BETWEEN ? AND ?
                                AND  `export_comptabilite`.`date_traitement` IS NOT NULL
                                AND  `export_comptabilite`.`FRS` = ?
                                AND  `export_comptabilite`.`date_comptabilisation` IS NULL
                                ORDER BY `export_comptabilite`.`date_traitement` DESC;
                     ",[$request->debut, $request->fin, $request->fournisseur]);
                }else{
                    $comptabiliser =  DB::select(
                        "SELECT  *           
                            FROM `export_comptabilite`  
                                WHERE  `export_comptabilite`.`dateCreate` BETWEEN ? AND ?
                                AND  `export_comptabilite`.`date_traitement` IS NOT NULL
                                AND  `export_comptabilite`.`date_comptabilisation` IS NULL
                                ORDER BY `export_comptabilite`.`date_traitement`DESC;

                    ",[$request->debut, $request->fin]);

                }
            }

            foreach ($comptabiliser as $key => $comptabilise) {
                if ($comptabilise->filleuls !== null) {
                     $compta = json_decode($comptabilise->filleuls);
                     $comptabiliser[$key]->clientFilleuls= $compta->nomPrenom;
                     $comptabiliser[$key]->clientFilleulsifu= $compta->ifu;
                     unset($comptabiliser[$key]->filleuls);
                }else{
                 $comptabiliser[$key]->clientFilleuls = '';
                 $comptabiliser[$key]->clientFilleulsifu = '';
                 unset($comptabiliser[$key]->filleuls);
                }
            }
            
            return redirect()->route('ventes.viewVenteTraiter')->withInput()->with('resultat',['comptabilisers'=>$comptabiliser,'debut'=>$request->debut, 'fin'=>$request->fin, 'filtre'=>$request->filtre]); 
    }

    public function postDejaExport(Request $request)
    {

        $comptabiliser =[];
        if($request->filtre == 'traitement'){
            session(['filtre'=>$request->filtre]);
            if($request->fournisseur){
                $comptabiliser =  DB::select(
                    "SELECT  *           
                        FROM `export_comptabilite`  
                            WHERE  `export_comptabilite`.`date_traitement` BETWEEN ? AND ?
                            AND  `export_comptabilite`.`date_comptabilisation` IS NOT NULL
                            AND  `export_comptabilite`.`FRS` =?
                            ORDER BY `export_comptabilite`.`date_traitement`DESC;
                    ",[$request->debut, $request->fin,$request->fournisseur]);
            }else{
            
                    
                $comptabiliser =  DB::select(
                    "SELECT  *           
                        FROM `export_comptabilite`  
                            WHERE  `export_comptabilite`.`date_comptabilisation` BETWEEN ? AND ?
                            ORDER BY `export_comptabilite`.`date_traitement`DESC;

                ",[$request->debut, $request->fin]);

            }
        }elseif ($request->filtre == 'comptabilisation'){
            session(['filtre'=>$request->filtre]);
            if($request->fournisseur){
                $comptabiliser =  DB::select(
                    "SELECT  *           
                        FROM `export_comptabilite`  
                            WHERE  `export_comptabilite`.`date_comptabilisation` BETWEEN ? AND ?
                            AND  `export_comptabilite`.`date_traitement` IS NOT NULL
                            AND  `export_comptabilite`.`FRS` = ?
                            ORDER BY `export_comptabilite`.`date_comptabilisation`DESC;
                    ",[$request->debut, $request->fin,$request->fournisseur]);
            }else{
            
                $comptabiliser =  DB::select(
                    "SELECT  *           
                        FROM `export_comptabilite`  
                            WHERE  `export_comptabilite`.`date_comptabilisation` BETWEEN ? AND ?
                            AND  `export_comptabilite`.`date_traitement` IS NOT NULL
                            ORDER BY `export_comptabilite`.`date_traitement`DESC;
                            
                ",[$request->debut, $request->fin]);
            }
        }else{
            session(['filtre'=>$request->filtre]);
            if($request->fournisseur){
                $comptabiliser =  DB::select(
                    "SELECT  *           
                        FROM `export_comptabilite`  
                            WHERE  `export_comptabilite`.`date_traitement` BETWEEN ? AND ?
                            AND  `export_comptabilite`.`date_comptabilisation` IS NOT NULL
                            AND  `export_comptabilite`.`FRS` = ?
                            ORDER BY `export_comptabilite`.`date_traitement`DESC;
                    ",[$request->debut, $request->fin,$request->fournisseur]);
            }else{
            
                $comptabiliser =  DB::select(
                    "SELECT  *           
                        FROM `export_comptabilite`  
                            WHERE  `export_comptabilite`.`dateCreate` BETWEEN ? AND ?
                            AND  `export_comptabilite`.`date_traitement` IS NOT NULL
                            AND  `export_comptabilite`.`date_comptabilisation` IS NOT NULL
                            ORDER BY `export_comptabilite`.`date_traitement`DESC;
                            
                ",[$request->debut, $request->fin]);
            }
        }
            /*  $comptabiliser =  DB::select("SELECT                 
                export_comptabilite.`heureSysteme`, export_comptabilite.`dateSysteme`,export_comptabilite.`code`,export_comptabilite.`id`, export_comptabilite.`dateVente`, 
                export_comptabilite.`clients`, export_comptabilite.`ifu`, export_comptabilite.dateAchat,  export_comptabilite.produit,
                export_comptabilite.qte,  export_comptabilite.pvr,export_comptabilite.prixTTC, 
                export_comptabilite.prixHt,export_comptabilite.`filleuls`,export_comptabilite.PrixBruite,export_comptabilite.NetHT,
                export_comptabilite.TVA, export_comptabilite.AIB,   export_comptabilite.TTC, export_comptabilite.FRS
                 FROM `export_comptabilite`  
                        WHERE `export_comptabilite`.`dateCreate` BETWEEN ? AND ?
                        AND  `export_comptabilite`.`date_traitement` IS NOT NULL
                        AND  `export_comptabilite`.`date_comptabilisation` IS NOT NULL;

                ",[$request->debut, $request->fin]); 
            */
            foreach ($comptabiliser as $key => $comptabilise) {
                if ($comptabilise->filleuls !== null) {
                     $compta = json_decode($comptabilise->filleuls);
                     $comptabiliser[$key]->clientFilleuls= $compta->nomPrenom;
                     $comptabiliser[$key]->clientFilleulsifu= $compta->ifu;
                     unset($comptabiliser[$key]->filleuls);
                }else{
                 $comptabiliser[$key]->clientFilleuls = '';
                 $comptabiliser[$key]->clientFilleulsifu = '';
                 unset($comptabiliser[$key]->filleuls);
                }
            }
            
            return redirect()->route('ventes.viewVenteComptabiliser')->withInput()->with('resultat',['comptabilisers'=>$comptabiliser,'debut'=>$request->debut, 'fin'=>$request->fin, 'filtre'=>$request->filtre]); 
    }
    
    public function export($debut, $fin, $filtre)
    {
        $fileName = 'comptabiliser';
        $date = date('Ymd');
        
        return Excel::download(new ComptabiliteExport($debut, $fin, $filtre), $fileName . '_' . $date .'.xlsx');
    }

    public function ReExport($debut, $fin,$filtre)
    {
        $fileName = 'ReExport_comptabiliser';
        $date = date('Ymd');

        return Excel::download(new ComptabiliteReExport($debut, $fin, $filtre), $fileName . '_' . $date .'.xlsx');
    }


}
