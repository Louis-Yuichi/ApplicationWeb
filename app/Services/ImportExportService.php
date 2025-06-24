<?php
// filepath: /home/etudiant/dt231159/STAGE/ApplicationWeb/app/Services/ImportExportService.php

namespace App\Services;

class ImportExportService
{
    private $columnMappings = [
        'candidat' => [
            'code' => 'numCandidat',
            'nom' => 'nom',
            'prénom' => 'prenom',
            'civilité' => 'civilite',
            'profil' => 'profil',
            'boursier' => 'boursier',
            'marqueur_dossier' => 'marqueurDossier',
            'marqueurs dossier' => 'marqueurDossier'
        ],
        'établissement' => [
            'nom établissement origine' => 'nomEtablissement',
            'commune etablissement origine - libellé' => 'villeEtablissement',
            'commune etablissement origine - codepostal' => 'codePostalEtablissement',
            'département etablissement origine' => 'departementEtablissement',
            'pays etablissement origine' => 'paysEtablissement'
        ],
        'note' => [
            'globale' => 'noteGlobale',
            'fiche avenir' => 'noteFicheAvenir',
            'lycée' => 'noteLycee',
            'dossier' => 'noteDossier'
        ]
    ];

    public function mapColumns($headers)
    {
        $mapping = [];
        
        foreach ($headers as $idx => $col) {
            if (empty($col)) continue;
            
            $colLower = mb_strtolower(trim($col));

            // Cas spéciaux d'établissement
            $etablissementCases = [
                'nom etablissement origine' => 'nomEtablissement',
                'commune etablissement origine - libellé' => 'villeEtablissement',
                'commune etablissement origine - codepostal' => 'codePostalEtablissement', 
                'département etablissement origine' => 'departementEtablissement',
                'pays etablissement origine' => 'paysEtablissement'
            ];

            foreach ($etablissementCases as $pattern => $field) {
                if (strpos($colLower, $pattern) !== false) {
                    $mapping[$field] = $idx;
                    continue 2;
                }
            }

            // Cas spéciaux exacts
            $specialCases = [
                'candidat - code' => 'numCandidat',
                'numéro parcoursup' => 'numCandidat',
                'candidat - nom' => 'nom',
                'candidat - prénom' => 'prenom',
                'nom' => 'nom',
                'prénom' => 'prenom',
                'profil' => 'profil',
                'marqueurs dossier' => 'marqueurDossier',
                'diplôme' => 'diplome',
                'serie' => 'serie',
                'commentaires' => 'commentaire',
                'en préparation / obtenu' => 'preparation_obtenu',
                'combinaison des enseignements de spécialité en terminale' => 'specialitesTerminale',
                'civilité' => 'civilite',
                'candidat boursier - code' => 'boursier'
            ];

            foreach ($specialCases as $pattern => $field) {
                if ($colLower === $pattern) {
                    $mapping[$field] = $idx;
                    continue 2;
                }
            }

            // Mapping flexible
            foreach ($this->columnMappings as $category => $fields) {
                foreach ($fields as $search => $dbField) {
                    if (!empty($search) && strpos($colLower, mb_strtolower($search)) !== false) {
                        $mapping[$dbField] = $idx;
                        continue 3;
                    }
                }
            }
        }

        return $mapping;
    }

    public function formatBoursier($value)
    {
        if (empty($value)) return '0';
        
        if (is_numeric($value)) {
            return (string)(intval($value) > 0 ? intval($value) : 0);
        }
        
        $value = strtolower(trim($value));
        return ($value === 'oui' || $value === '1' || $value === 'true') ? '1' : '0';
    }

    public function processFieldValue($field, $value)
    {
        // Champs numériques
        if (in_array($field, ['noteDossier', 'noteGlobale'])) {
            return empty($value) || !is_numeric($value) ? 0 : floatval($value);
        }
        
        // Champ boursier
        if ($field === 'boursier') {
            return $this->formatBoursier($value);
        }
        
        // Champs texte
        $value = trim($value);
        if (empty($value)) return '-';
        
        // Limiter la longueur
        $maxLengths = [
            'nom' => 100,
            'prenom' => 100,
            'civilite' => 10,
            'profil' => 50,
            'commentaire' => 500
        ];
        
        if (isset($maxLengths[$field])) {
            $value = substr($value, 0, $maxLengths[$field]);
        }
        
        return $value;
    }

    public function generateCSV($data, $headers, $filename)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // En-têtes
        fputcsv($output, $headers);
        
        // Données
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    }

    public function getOrCreateEtablissement($model, $data)
    {
        $etablissement = $model->where('nomEtablissement', $data['nomEtablissement'])
                              ->where('villeEtablissement', $data['villeEtablissement'])
                              ->first();
        
        if (!$etablissement) {
            $model->insert($data);
            $etablissement = $model->where('nomEtablissement', $data['nomEtablissement'])
                                  ->where('villeEtablissement', $data['villeEtablissement'])
                                  ->first();
        }
        
        return $etablissement;
    }
}