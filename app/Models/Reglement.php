<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reglement extends Model
{
    use HasFactory;
    protected $fillable = [
        'code','reference', 'date', 'montant','document', 'vente_id', 'compte_id', 'type_detail_recu_id','user_id','observation_validation','user_validateur_id','statut'
    ];
    public function vente()
    {
        return $this->belongsTo(Vente::class, 'vente_id', 'id');
    }


    public function compte()
    {
        return $this->belongsTo(Compte::class, 'compte_id', 'id');
    }

    public function typeReglement()
    {
        return $this->belongsTo(TypeDetailRecu::class, 'type_detail_recu_id', 'id');
    }
	public function utilisateur(){
        return $this->belongsTo(User::class,'user_id');
    }
}
