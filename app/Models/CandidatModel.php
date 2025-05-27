<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidatModel extends Model
{
    protected $table = 'candidat';
    protected $primaryKey = 'numCandidat';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = [
        'numCandidat',
        'anneeUniversitaire',
        'nom',
        'prenom',
        'profil',
        'groupe',
        'marqueurDossier',
        'scolarite',
        'diplome',
        'preparation_obtenu',
        'serie',
        'specialitesTerminale',
        'specialiteAbandonne',
        'noteLycee',
        'noteFicheAvenir',
        'noteDossier',
        'commentaire'
    ];
}
