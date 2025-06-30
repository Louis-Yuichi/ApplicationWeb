<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class ExportController extends Controller
{
    public function exporterPDF($idEtudiant)
    {
        try {
            // Si les données sont envoyées via POST, les utiliser
            if ($this->request->getMethod() === 'POST') {
                $donneesPDF = json_decode($this->request->getPost('donneesPDF'), true);
                $this->genererPDFAvecDonnees($donneesPDF);
            } else {
                // Fallback : générer un PDF simple
                $this->genererPDFSimple($idEtudiant);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Erreur export PDF: ' . $e->getMessage());
            echo '<h1>Erreur Export PDF</h1>';
            echo '<p>' . $e->getMessage() . '</p>';
        }
    }
    
    private function genererPDFAvecDonnees($donnees)
    {
        require_once ROOTPATH . 'vendor/setasign/fpdf/fpdf.php';
        
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10); // Marges réduites : gauche, haut, droite
        $pdf->SetFont('Arial', '', 7);

        // UN SEUL LOGO à gauche - RÉDUIT À 70%
        $logoIUT = ROOTPATH . 'public/assets/images/logo_dept_mini_coul.png';
        
        if (file_exists($logoIUT)) {
            $pdf->Image($logoIUT, 12, 8, 20, 14); // Réduit : 20x14
        } else {
            $pdf->Rect(12, 8, 20, 14);
            $pdf->SetXY(14, 12);
            $pdf->SetFont('Arial', '', 6);
            $pdf->Cell(16, 4, 'Logo IUT', 0, 0, 'C');
        }

        // En-tête principal - POSITION AJUSTÉE
        $pdf->SetXY(10, 25); // Y réduit de 30 à 25
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 5, utf8_decode('Fiche Avis Poursuite d\'Études - Promotion ' . $donnees['anneePromotion']), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 4, utf8_decode('Département Informatique IUT Le Havre'), 0, 1, 'C'); // Hauteur réduite
        
        $pdf->Ln(3); // Espacement réduit

        // TITRE FICHE D'INFORMATION
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(0, 5, utf8_decode('FICHE D\'INFORMATION ÉTUDIANT(E)'), 0, 1, 'L'); // Hauteur réduite
        
        $pdf->SetFont('Arial', '', 7);
        
        // NOM - Prénom - HAUTEUR AUGMENTÉE
        $pdf->Cell(45, 5, utf8_decode('NOM - Prénom :'), 1, 0, 'L'); // 4→5
        $pdf->Cell(145, 5, utf8_decode($donnees['nomEtudiant']), 1, 1, 'L'); // 4→5
        
        // Apprentissage - HAUTEUR AUGMENTÉE
        $pdf->Cell(45, 5, 'Apprentissage (oui/non)', 1, 0, 'L'); // 4→5
        $pdf->Cell(24, 5, 'BUT1', 1, 0, 'C'); // 4→5
        $pdf->Cell(24, 5, $donnees['apprentissageBUT1'] ?? 'Non', 1, 0, 'C'); // 4→5
        $pdf->Cell(24, 5, 'BUT2', 1, 0, 'C'); // 4→5
        $pdf->Cell(24, 5, $donnees['apprentissageBUT2'] ?? 'Non', 1, 0, 'C'); // 4→5
        $pdf->Cell(24, 5, 'BUT3', 1, 0, 'C'); // 4→5
        $pdf->Cell(25, 5, utf8_decode($donnees['apprentissageBUT3'] ?? 'Non'), 1, 1, 'C'); // 4→5
        
        // Parcours d'études - HAUTEUR AUGMENTÉE
        $pdf->Cell(45, 5, utf8_decode('Parcours d\'études :'), 1, 0, 'L'); // 4→5
        $pdf->Cell(24, 5, 'n-2', 1, 0, 'C'); // 4→5
        $pdf->Cell(24, 5, $donnees['parcoursN2'] ?? '', 1, 0, 'C'); // 4→5
        $pdf->Cell(24, 5, 'n-1', 1, 0, 'C'); // 4→5
        $pdf->Cell(24, 5, $donnees['parcoursN1'] ?? '', 1, 0, 'C'); // 4→5
        $pdf->Cell(24, 5, 'n', 1, 0, 'C'); // 4→5
        $pdf->Cell(25, 5, $donnees['parcoursN'] ?? '', 1, 1, 'C'); // 4→5
        
        // Parcours BUT - HAUTEUR AUGMENTÉE
        $pdf->Cell(45, 5, 'Parcours BUT', 1, 0, 'L'); // 4→5
        $pdf->Cell(145, 5, utf8_decode($donnees['parcoursBUT'] ?? ''), 1, 1, 'L'); // 4→5
        
        // Mobilité - HAUTEUR AUGMENTÉE
        $pdf->Cell(45, 5, utf8_decode('Si mobilité à l\'étranger (lieu, durée)'), 1, 0, 'L'); // 4→5
        $pdf->Cell(145, 5, utf8_decode($donnees['mobiliteEtranger'] ?? ''), 1, 1, 'L'); // 4→5
        
        $pdf->Ln(5); // Espacement augmenté de 4 à 5

        // RESULTATS DES COMPETENCES
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(0, 6, utf8_decode('RÉSULTATS DES COMPÉTENCES'), 0, 1, 'L'); // 5→6
        
        // Premier tableau : BUT 1 et BUT 2 - EN-TÊTES AUGMENTÉS
        $pdf->SetFont('Arial', 'B', 6);
        
        // Ligne 1 : cellule vide + BUT 1 + BUT 2 - HAUTEUR AUGMENTÉE
        $pdf->Cell(105, 6, '', 1, 0, 'C'); // 5→6
        $pdf->Cell(42, 6, 'BUT 1', 1, 0, 'C'); // 5→6
        $pdf->Cell(43, 6, 'BUT 2', 1, 1, 'C'); // 5→6
        
        // Ligne 2 : cellule vide + Moy/Rang + Moy/Rang - HAUTEUR AUGMENTÉE
        $pdf->Cell(105, 6, '', 1, 0, 'C'); // 5→6
        $pdf->Cell(21, 6, 'Moy.', 1, 0, 'C'); // 5→6
        $pdf->Cell(21, 6, 'Rang', 1, 0, 'C'); // 5→6
        $pdf->Cell(21, 6, 'Moy.', 1, 0, 'C'); // 5→6
        $pdf->Cell(22, 6, 'Rang', 1, 1, 'C'); // 5→6

        $pdf->SetFont('Arial', '', 6);
        
        // Compétences UE1 à UE6 - HAUTEUR AUGMENTÉE
        $competences_noms = [
            1 => 'UE1 - Réaliser des applications',
            2 => 'UE2 - Optimiser des applications',
            3 => 'UE3 - Administrer des systèmes', 
            4 => 'UE4 - Gérer des données',
            5 => 'UE5 - Conduire des projets',
            6 => 'UE6 - Collaborer'
        ];
        
        foreach ($competences_noms as $num => $nom) {
            $pdf->Cell(105, 4.5, utf8_decode($nom), 1, 0, 'L'); // 3.5→4.5
            
            // BUT 1
            $moy1 = $donnees['competences']["BIN{$num}_but1_moy"] ?? '';
            $rang1 = $donnees['competences']["BIN{$num}_but1_rang"] ?? '';
            $pdf->Cell(21, 4.5, $moy1, 1, 0, 'C'); // 3.5→4.5
            $pdf->Cell(21, 4.5, $rang1, 1, 0, 'C'); // 3.5→4.5
            
            // BUT 2
            $moy2 = $donnees['competences']["BIN{$num}_but2_moy"] ?? '';
            $rang2 = $donnees['competences']["BIN{$num}_but2_rang"] ?? '';
            $pdf->Cell(21, 4.5, $moy2, 1, 0, 'C'); // 3.5→4.5
            $pdf->Cell(22, 4.5, $rang2, 1, 1, 'C'); // 3.5→4.5
        }
        
        // Maths - HAUTEUR AUGMENTÉE
        $pdf->Cell(105, 4.5, 'Maths', 1, 0, 'L'); // 3.5→4.5
        $moy1 = $donnees['ressources']['maths_but1_moy'] ?? '';
        $rang1 = $donnees['ressources']['maths_but1_rang'] ?? '';
        $moy2 = $donnees['ressources']['maths_but2_moy'] ?? '';
        $rang2 = $donnees['ressources']['maths_but2_rang'] ?? '';
        $pdf->Cell(21, 4.5, $moy1, 1, 0, 'C'); // 3.5→4.5
        $pdf->Cell(21, 4.5, $rang1, 1, 0, 'C'); // 3.5→4.5
        $pdf->Cell(21, 4.5, $moy2, 1, 0, 'C'); // 3.5→4.5
        $pdf->Cell(22, 4.5, $rang2, 1, 1, 'C'); // 3.5→4.5
        
        // Anglais - HAUTEUR AUGMENTÉE
        $pdf->Cell(105, 4.5, 'Anglais', 1, 0, 'L'); // 3.5→4.5
        $moy1 = $donnees['ressources']['anglais_but1_moy'] ?? '';
        $rang1 = $donnees['ressources']['anglais_but1_rang'] ?? '';
        $moy2 = $donnees['ressources']['anglais_but2_moy'] ?? '';
        $rang2 = $donnees['ressources']['anglais_but2_rang'] ?? '';
        $pdf->Cell(21, 4.5, $moy1, 1, 0, 'C'); // 3.5→4.5
        $pdf->Cell(21, 4.5, $rang1, 1, 0, 'C'); // 3.5→4.5
        $pdf->Cell(21, 4.5, $moy2, 1, 0, 'C'); // 3.5→4.5
        $pdf->Cell(22, 4.5, $rang2, 1, 1, 'C'); // 3.5→4.5
        
        // Absences - HAUTEUR AUGMENTÉE
        $pdf->Cell(105, 4.5, utf8_decode('Nombre d\'absences injustifiées'), 1, 0, 'L'); // 3.5→4.5
        $pdf->Cell(42, 4.5, $donnees['absences']['but1'] ?? '', 1, 0, 'C'); // 3.5→4.5
        $pdf->Cell(43, 4.5, $donnees['absences']['but2'] ?? '', 1, 1, 'C'); // 3.5→4.5

        $pdf->Ln(5); // Espacement augmenté de 4 à 5

        // Deuxième tableau : BUT 3 - S5 - EN-TÊTES AUGMENTÉS
        $pdf->SetFont('Arial', 'B', 6);
        
        // En-têtes BUT 3 - HAUTEUR AUGMENTÉE
        $pdf->Cell(105, 6, '', 1, 0, 'C'); // 5→6
        $pdf->Cell(43, 6, 'BUT 3 - S5', 1, 1, 'C'); // 5→6
        
        $pdf->Cell(105, 6, '', 1, 0, 'C'); // 5→6
        $pdf->Cell(21, 6, 'Moy.', 1, 0, 'C'); // 5→6
        $pdf->Cell(22, 6, 'Rang', 1, 1, 'C'); // 5→6
        
        $pdf->SetFont('Arial', '', 6);
        
        // Compétences BUT 3 - HAUTEUR AUGMENTÉE
        foreach ($competences_noms as $num => $nom) {
            $pdf->Cell(105, 4.5, utf8_decode($nom), 1, 0, 'L'); // 3.5→4.5
            
            if (in_array($num, [1, 2, 6])) {
                $moy3 = $donnees['competences']["BIN{$num}_but3_moy"] ?? '';
                $rang3 = $donnees['competences']["BIN{$num}_but3_rang"] ?? '';
                $pdf->Cell(21, 4.5, $moy3, 1, 0, 'C'); // 3.5→4.5
                $pdf->Cell(22, 4.5, $rang3, 1, 1, 'C'); // 3.5→4.5
            } else {
                // BARRER LES UE3, UE4, UE5 DU BUT 3
                $currentY = $pdf->GetY();
                $pdf->Cell(21, 4.5, '', 1, 0, 'C'); // 3.5→4.5
                $pdf->Cell(22, 4.5, '', 1, 1, 'C'); // 3.5→4.5
                
                // Dessiner une ligne pour barrer le texte - POSITION AJUSTÉE
                $pdf->Line(10, $currentY + 2.25, 115, $currentY + 2.25); // 1.75→2.25
            }
        }
        
        // Maths BUT 3 - HAUTEUR AUGMENTÉE
        $pdf->Cell(105, 4.5, 'Maths', 1, 0, 'L'); // 3.5→4.5
        $moy3 = $donnees['ressources']['maths_but3_moy'] ?? '';
        $rang3 = $donnees['ressources']['maths_but3_rang'] ?? '';
        $pdf->Cell(21, 4.5, $moy3, 1, 0, 'C'); // 3.5→4.5
        $pdf->Cell(22, 4.5, $rang3, 1, 1, 'C'); // 3.5→4.5
        
        // Absences BUT 3 - HAUTEUR AUGMENTÉE
        $pdf->Cell(105, 4.5, utf8_decode('Nombre d\'absences injustifiées'), 1, 0, 'L'); // 3.5→4.5
        $pdf->Cell(43, 4.5, $donnees['absences']['but3'] ?? '', 1, 1, 'C'); // 3.5→4.5

        $pdf->Ln(6); // Espacement augmenté de 3 à 6

        // AVIS - TITRE + TABLEAU CORRIGÉ
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(0, 5, utf8_decode('Avis de l\'équipe pédagogique pour la poursuite d\'études après le BUT3'), 0, 1, 'L'); // 4→5
        
        $pdf->SetFont('Arial', 'B', 5);
        
        // En-têtes des avis - HAUTEUR AUGMENTÉE
        $pdf->Cell(30, 8, '', 1, 0, 'C'); // 7→8
        $pdf->Cell(40, 8, '', 1, 0, 'C'); // 7→8
        $pdf->Cell(24, 8, utf8_decode('Très Favorable'), 1, 0, 'C'); // 7→8
        $pdf->Cell(24, 8, 'Favorable', 1, 0, 'C'); // 7→8
        $pdf->Cell(24, 8, utf8_decode('Assez Favorable'), 1, 0, 'C'); // 7→8
        $pdf->Cell(24, 8, utf8_decode('Sans avis'), 1, 0, 'C'); // 7→8
        $pdf->Cell(24, 8, utf8_decode('Réservé'), 1, 1, 'C'); // 7→8

        $pdf->SetFont('Arial', '', 6);
        
        // PREMIÈRE LIGNE : Pour l'étudiant + En école d'ingénieurs - HAUTEUR AUGMENTÉE
        $posY = $pdf->GetY();
        $pdf->Cell(30, 12, utf8_decode('Pour l\'étudiant'), 1, 0, 'C'); // 10→12
        $pdf->Cell(40, 6, utf8_decode('En école d\'ingénieurs'), 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, ($donnees['avis']['ecole_ingenieur'] === 'tres_favorable' ? 'X' : ''), 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, ($donnees['avis']['ecole_ingenieur'] === 'favorable' ? 'X' : ''), 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, ($donnees['avis']['ecole_ingenieur'] === 'assez_favorable' ? 'X' : ''), 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, ($donnees['avis']['ecole_ingenieur'] === 'sans_avis' ? 'X' : ''), 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, ($donnees['avis']['ecole_ingenieur'] === 'reserve' ? 'X' : ''), 1, 1, 'C'); // 5→6
        
        // DEUXIÈME LIGNE : En master (aligné sous école d'ingénieurs) - HAUTEUR AUGMENTÉE
        $pdf->SetXY(40, $pdf->GetY()); // Position ajustée avec nouvelles marges
        $pdf->Cell(40, 6, 'En master', 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, ($donnees['avis']['master'] === 'tres_favorable' ? 'X' : ''), 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, ($donnees['avis']['master'] === 'favorable' ? 'X' : ''), 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, ($donnees['avis']['master'] === 'assez_favorable' ? 'X' : ''), 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, ($donnees['avis']['master'] === 'sans_avis' ? 'X' : ''), 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, ($donnees['avis']['master'] === 'reserve' ? 'X' : ''), 1, 1, 'C'); // 5→6

        // TROISIÈME LIGNE : Nombre d'avis + En école d'ingénieurs (stats) - HAUTEUR AUGMENTÉE
        $posY = $pdf->GetY();
        
        // Case "Nombre d'avis" avec texte centré - TAILLE AUGMENTÉE
        $pdf->Cell(30, 12, '', 1, 0, 'C'); // 10→12
        $pdf->SetXY(10, $posY + 2);
        $pdf->Cell(30, 2.5, utf8_decode('Nombre d\'avis'), 0, 0, 'C');
        $pdf->SetXY(10, $posY + 5);
        $pdf->Cell(30, 2.5, 'pour la promotion', 0, 0, 'C');
        $pdf->SetXY(10, $posY + 8);
        $pdf->Cell(30, 2.5, '(total : ' . ($donnees['stats']['totalAvisPromotion'] ?? '0') . ')', 0, 0, 'C');
        
        // Stats école d'ingénieurs - HAUTEUR AUGMENTÉE
        $pdf->SetXY(40, $posY);
        $pdf->Cell(40, 6, utf8_decode('En école d\'ingénieurs'), 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, $donnees['stats']['ecole_ingenieur']['tres_favorable'] ?? '0', 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, $donnees['stats']['ecole_ingenieur']['favorable'] ?? '0', 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, $donnees['stats']['ecole_ingenieur']['assez_favorable'] ?? '0', 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, $donnees['stats']['ecole_ingenieur']['sans_avis'] ?? '0', 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, $donnees['stats']['ecole_ingenieur']['reserve'] ?? '0', 1, 1, 'C'); // 5→6
        
        // QUATRIÈME LIGNE : En master (stats) - HAUTEUR AUGMENTÉE
        $pdf->SetXY(40, $posY + 6);
        $pdf->Cell(40, 6, 'En master', 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, $donnees['stats']['master']['tres_favorable'] ?? '0', 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, $donnees['stats']['master']['favorable'] ?? '0', 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, $donnees['stats']['master']['assez_favorable'] ?? '0', 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, $donnees['stats']['master']['sans_avis'] ?? '0', 1, 0, 'C'); // 5→6
        $pdf->Cell(24, 6, $donnees['stats']['master']['reserve'] ?? '0', 1, 1, 'C'); // 5→6
        
        // CINQUIÈME LIGNE : Commentaire - HAUTEUR AUGMENTÉE
        $pdf->SetFont('Arial', '', 6);
        $pdf->Cell(30, 7, 'Commentaire', 1, 0, 'C'); // 6→7
        $pdf->Cell(160, 7, utf8_decode(substr($donnees['avis']['commentaire'] ?? '', 0, 100)), 1, 1, 'L'); // 6→7

        // Signatures compactes - ESPACEMENT AUGMENTÉ
        $pdf->Ln(8); // Espacement augmenté de 5 à 8
        $pdf->SetFont('Arial', '', 6);
        
        // Cadre nom avec texte en haut - HAUTEUR AUGMENTÉE
        $pdf->SetXY(125, $pdf->GetY() + 1); // Position ajustée
        $pdf->Cell(50, 3, utf8_decode('Nom du chef de Dépt.'), 0, 1, 'C'); // 2.5→3
        $pdf->Rect(125, $pdf->GetY(), 50, 6); // 5→6

        // AJOUTER LE NOM "Test" DANS LE CADRE - POSITION AJUSTÉE
        $pdf->SetXY(125, $pdf->GetY() + 2.5);
        $pdf->Cell(50, 2, 'M. Rodolphe Charrier', 0, 1, 'C');
        
        // Espacement entre les deux cadres - AJUSTÉ
        $pdf->SetXY(125, $pdf->GetY() + 7);
        
        // Cadre signature avec texte en haut - HAUTEUR AUGMENTÉE
        $pdf->Cell(50, 3, utf8_decode('Signature et cachet du Dépt'), 0, 1, 'C'); // 2.5→3

        // Télécharger le PDF
        $nomFichier = 'Fiche_Avis_' . str_replace(' ', '_', $donnees['nomEtudiant']) . '.pdf';
        $pdf->Output('D', $nomFichier);
    }
    
    private function genererPDFSimple($idEtudiant)
    {
        require_once ROOTPATH . 'vendor/setasign/fpdf/fpdf.php';
        
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(40, 10, 'Export PDF pour etudiant: ' . $idEtudiant);
        $pdf->Output('D', 'fiche_' . $idEtudiant . '.pdf');
    }
}