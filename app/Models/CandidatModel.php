<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidatModel extends Model
{
    protected $table = 'Candidat';
    protected $primaryKey = 'numCandidat';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

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

    protected $validationRules = [
        'numCandidat' => 'required|string',
        'anneeUniversitaire' => 'required|string',
        'nom' => 'required|string',
        'prenom' => 'required|string',
        'civilite' => 'permit_empty|string',
        'profil' => 'permit_empty|string',
        'boursier' => 'permit_empty|string',
        'marqueurDossier' => 'permit_empty|string',
        'scolarite' => 'permit_empty|string',
        'formation' => 'permit_empty|string',
        'diplome' => 'permit_empty|string',
        'typeDiplomeCode' => 'permit_empty|string',
        'preparation_obtenu' => 'permit_empty|string',
        'serie' => 'permit_empty|string',
        'serieCode' => 'permit_empty|string',
        'specialitesTerminale' => 'permit_empty|string',
        'specialiteAbandonne' => 'permit_empty|string',
        'specialiteMention' => 'permit_empty|string',
        'noteDossier' => 'permit_empty|decimal',
        'noteGlobale' => 'permit_empty|decimal',
        'commentaire' => 'permit_empty|string'
    ];

    protected $validationMessages = [
        'numCandidat' => [
            'required' => 'Le numéro de candidat est requis'
        ],
        'anneeUniversitaire' => [
            'required' => 'L\'année universitaire est requise'
        ],
        'nom' => [
            'required' => 'Le nom est requis'
        ],
        'prenom' => [
            'required' => 'Le prénom est requis'
        ]
    ];

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
