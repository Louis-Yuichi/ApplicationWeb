<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidatModel extends Model
{
    protected $table = 'Candidat';
    protected $primaryKey = ['numCandidat', 'anneeUniversitaire']; // Clé primaire composite
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'numCandidat',
        'anneeUniversitaire',
        'nom',
        'prenom',
        'civilite',
        'profil',
        'boursier',
        'marqueurDossier',
        'scolarite',
        'formation',
        'diplome',
        'typeDiplomeCode',
        'preparation_obtenu',
        'serie',
        'serieCode',
        'specialitesTerminale',
        'specialiteAbandonne',
        'specialiteMention',
        'noteDossier',
        'noteGlobale',
        'commentaire'
    ];

    protected $useTimestamps = false;
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

    // Relation avec Etablissement via EtudierDans
    public function etablissements()
    {
        return $this->belongsToMany(
            'App\Models\EtablissementModel',
            'EtudierDans',
            'numCandidat',
            'idEtablissement',
            'numCandidat',
            'idEtablissement'
        );
    }

    public function getEtablissementNotes($numCandidat, $idEtablissement)
    {
        $db = \Config\Database::connect();
        return $db->table('EtudierDans')
                ->where('numCandidat', $numCandidat)
                ->where('idEtablissement', $idEtablissement)
                ->get()
                ->getRow();
    }
}
