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
        $pdf->SetFont('Arial', '', 7);

        // UN SEUL LOGO à gauche - RÉDUIT À 80%
        $logoIUT = ROOTPATH . 'public/assets/images/logo_dept_mini_coul.png';
        
        if (file_exists($logoIUT)) {
            $pdf->Image($logoIUT, 15, 8, 24, 16); // 80% de 30x20 = 24x16
        } else {
            $pdf->Rect(15, 8, 24, 16);
            $pdf->SetXY(17, 12);
            $pdf->SetFont('Arial', '', 6);
            $pdf->Cell(20, 4, 'Logo IUT', 0, 0, 'C');
        }

        // En-tête principal
        $pdf->SetXY(15, 30);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, utf8_decode('Fiche Avis Poursuite d\'Études - Promotion ' . $donnees['anneePromotion']), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 5, utf8_decode('Département Informatique IUT Le Havre'), 0, 1, 'C');
        
        $pdf->Ln(4);

        // TITRE FICHE D'INFORMATION
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(0, 6, utf8_decode('FICHE D\'INFORMATION ÉTUDIANT(E)'), 0, 1, 'L');
        
        $pdf->SetFont('Arial', '', 7);
        
        // NOM - Prénom
        $pdf->Cell(45, 5, utf8_decode('NOM - Prénom :'), 1, 0, 'L');
        $pdf->Cell(145, 5, utf8_decode($donnees['nomEtudiant']), 1, 1, 'L');
        
        // Apprentissage
        $pdf->Cell(45, 5, 'Apprentissage (oui/non)', 1, 0, 'L');
        $pdf->Cell(24, 5, 'BUT1', 1, 0, 'C');
        $pdf->Cell(24, 5, $donnees['apprentissageBUT1'] ?? 'Non', 1, 0, 'C');
        $pdf->Cell(24, 5, 'BUT2', 1, 0, 'C');
        $pdf->Cell(24, 5, $donnees['apprentissageBUT2'] ?? 'Non', 1, 0, 'C');
        $pdf->Cell(24, 5, 'BUT3', 1, 0, 'C');
        $pdf->Cell(25, 5, utf8_decode($donnees['apprentissageBUT3'] ?? 'Non'), 1, 1, 'C');
        
        // Parcours d'études
        $pdf->Cell(45, 5, utf8_decode('Parcours d\'études :'), 1, 0, 'L');
        $pdf->Cell(24, 5, 'n-2', 1, 0, 'C');
        $pdf->Cell(24, 5, $donnees['parcoursN2'] ?? '', 1, 0, 'C');
        $pdf->Cell(24, 5, 'n-1', 1, 0, 'C'); 
        $pdf->Cell(24, 5, $donnees['parcoursN1'] ?? '', 1, 0, 'C');
        $pdf->Cell(24, 5, 'n', 1, 0, 'C');
        $pdf->Cell(25, 5, $donnees['parcoursN'] ?? '', 1, 1, 'C');
        
        // Parcours BUT
        $pdf->Cell(45, 5, 'Parcours BUT', 1, 0, 'L');
        $pdf->Cell(145, 5, utf8_decode($donnees['parcoursBUT'] ?? ''), 1, 1, 'L');
        
        // Mobilité - TEXTE EXACT
        $pdf->Cell(45, 5, utf8_decode('Si mobilité à l\'étranger (lieu, durée)'), 1, 0, 'L');
        $pdf->Cell(145, 5, utf8_decode($donnees['mobiliteEtranger'] ?? ''), 1, 1, 'L');
        
        $pdf->Ln(6);

        // RESULTATS DES COMPETENCES - TABLEAUX PLUS GRANDS
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(0, 6, utf8_decode('RÉSULTATS DES COMPÉTENCES'), 0, 1, 'L');
        
        // Premier tableau : BUT 1 et BUT 2 - PLUS GRAND
        $pdf->SetFont('Arial', 'B', 6);
        
        // Ligne 1 : cellule vide + BUT 1 + BUT 2
        $pdf->Cell(105, 6, '', 1, 0, 'C'); // Plus large
        $pdf->Cell(42, 6, 'BUT 1', 1, 0, 'C');
        $pdf->Cell(43, 6, 'BUT 2', 1, 1, 'C');
        
        // Ligne 2 : cellule vide + Moy/Rang + Moy/Rang  
        $pdf->Cell(105, 6, '', 1, 0, 'C');
        $pdf->Cell(21, 6, 'Moy.', 1, 0, 'C');
        $pdf->Cell(21, 6, 'Rang', 1, 0, 'C');
        $pdf->Cell(21, 6, 'Moy.', 1, 0, 'C');
        $pdf->Cell(22, 6, 'Rang', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 6);
        
        // Compétences UE1 à UE6
        $competences_noms = [
            1 => 'UE1 - Réaliser des applications',
            2 => 'UE2 - Optimiser des applications',
            3 => 'UE3 - Administrer des systèmes', 
            4 => 'UE4 - Gérer des données',
            5 => 'UE5 - Conduire des projets',
            6 => 'UE6 - Collaborer'
        ];
        
        foreach ($competences_noms as $num => $nom) {
            $pdf->Cell(105, 4, utf8_decode($nom), 1, 0, 'L');
            
            // BUT 1
            $moy1 = $donnees['competences']["BIN{$num}_but1_moy"] ?? '';
            $rang1 = $donnees['competences']["BIN{$num}_but1_rang"] ?? '';
            $pdf->Cell(21, 4, $moy1, 1, 0, 'C');
            $pdf->Cell(21, 4, $rang1, 1, 0, 'C');
            
            // BUT 2
            $moy2 = $donnees['competences']["BIN{$num}_but2_moy"] ?? '';
            $rang2 = $donnees['competences']["BIN{$num}_but2_rang"] ?? '';
            $pdf->Cell(21, 4, $moy2, 1, 0, 'C');
            $pdf->Cell(22, 4, $rang2, 1, 1, 'C');
        }
        
        // Maths
        $pdf->Cell(105, 4, 'Maths', 1, 0, 'L');
        $moy1 = $donnees['ressources']['maths_but1_moy'] ?? '';
        $rang1 = $donnees['ressources']['maths_but1_rang'] ?? '';
        $moy2 = $donnees['ressources']['maths_but2_moy'] ?? '';
        $rang2 = $donnees['ressources']['maths_but2_rang'] ?? '';
        $pdf->Cell(21, 4, $moy1, 1, 0, 'C');
        $pdf->Cell(21, 4, $rang1, 1, 0, 'C');
        $pdf->Cell(21, 4, $moy2, 1, 0, 'C');
        $pdf->Cell(22, 4, $rang2, 1, 1, 'C');
        
        // Anglais
        $pdf->Cell(105, 4, 'Anglais', 1, 0, 'L');
        $moy1 = $donnees['ressources']['anglais_but1_moy'] ?? '';
        $rang1 = $donnees['ressources']['anglais_but1_rang'] ?? '';
        $moy2 = $donnees['ressources']['anglais_but2_moy'] ?? '';
        $rang2 = $donnees['ressources']['anglais_but2_rang'] ?? '';
        $pdf->Cell(21, 4, $moy1, 1, 0, 'C');
        $pdf->Cell(21, 4, $rang1, 1, 0, 'C');
        $pdf->Cell(21, 4, $moy2, 1, 0, 'C');
        $pdf->Cell(22, 4, $rang2, 1, 1, 'C');
        
        // Absences
        $pdf->Cell(105, 4, utf8_decode('Nombre d\'absences injustifiées'), 1, 0, 'L');
        $pdf->Cell(42, 4, $donnees['absences']['but1'] ?? '', 1, 0, 'C');
        $pdf->Cell(43, 4, $donnees['absences']['but2'] ?? '', 1, 1, 'C');

        $pdf->Ln(6);

        // Deuxième tableau : BUT 3 - S5 - MÊME LARGEUR
        $pdf->SetFont('Arial', 'B', 6);
        
        // En-têtes BUT 3
        $pdf->Cell(105, 6, '', 1, 0, 'C');
        $pdf->Cell(43, 6, 'BUT 3 - S5', 1, 1, 'C');
        
        $pdf->Cell(105, 6, '', 1, 0, 'C');
        $pdf->Cell(21, 6, 'Moy.', 1, 0, 'C');
        $pdf->Cell(22, 6, 'Rang', 1, 1, 'C');
        
        $pdf->SetFont('Arial', '', 6);
        
        // Compétences BUT 3
        foreach ($competences_noms as $num => $nom) {
            $pdf->Cell(105, 4, utf8_decode($nom), 1, 0, 'L');
            
            if (in_array($num, [1, 2, 6])) {
                $moy3 = $donnees['competences']["BIN{$num}_but3_moy"] ?? '';
                $rang3 = $donnees['competences']["BIN{$num}_but3_rang"] ?? '';
                $pdf->Cell(21, 4, $moy3, 1, 0, 'C');
                $pdf->Cell(22, 4, $rang3, 1, 1, 'C');
            } else {
                $pdf->Cell(21, 4, '', 1, 0, 'C');
                $pdf->Cell(22, 4, '', 1, 1, 'C');
            }
        }
        
        // Maths BUT 3
        $pdf->Cell(105, 4, 'Maths', 1, 0, 'L');
        $moy3 = $donnees['ressources']['maths_but3_moy'] ?? '';
        $rang3 = $donnees['ressources']['maths_but3_rang'] ?? '';
        $pdf->Cell(21, 4, $moy3, 1, 0, 'C');
        $pdf->Cell(22, 4, $rang3, 1, 1, 'C');
        
        // Absences BUT 3
        $pdf->Cell(105, 4, utf8_decode('Nombre d\'absences injustifiées'), 1, 0, 'L');
        $pdf->Cell(43, 4, $donnees['absences']['but3'] ?? '', 1, 1, 'C');

        $pdf->Ln(6);

        // AVIS - TITRE + TABLEAU RÉDUIT ET CENTRÉ
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(0, 6, utf8_decode('Avis de l\'équipe pédagogique pour la poursuite d\'études après le BUT3'), 0, 1, 'L');
        
        $pdf->SetFont('Arial', 'B', 5);
        
        // En-têtes des avis - HAUTEUR 12 AVEC ESPACES AU LIEU DE SAUTS DE LIGNE
        $pdf->Cell(30, 12, '', 1, 0, 'C'); // Première colonne
        $pdf->Cell(40, 12, '', 1, 0, 'C'); // Deuxième colonne
        $pdf->Cell(24, 12, utf8_decode('Très Favorable'), 1, 0, 'C'); // Espace au lieu de saut de ligne
        $pdf->Cell(24, 12, 'Favorable', 1, 0, 'C');
        $pdf->Cell(24, 12, utf8_decode('Assez Favorable'), 1, 0, 'C'); // Espace au lieu de saut de ligne
        $pdf->Cell(24, 12, utf8_decode('Sans avis'), 1, 0, 'C'); // Espace au lieu de saut de ligne
        $pdf->Cell(24, 12, utf8_decode('Réservé'), 1, 1, 'C');

        $pdf->SetFont('Arial', '', 6);
        
        // Lignes d'avis - "Pour l'étudiant" UNE SEULE CASE
        $pdf->Cell(30, 12, utf8_decode('Pour l\'étudiant'), 1, 0, 'C'); // Case unifiée
        $pdf->Cell(40, 6, utf8_decode('En école d\'ingénieurs'), 1, 0, 'C');
        $pdf->Cell(24, 6, ($donnees['avis']['ecole_ingenieur'] === 'tres_favorable' ? 'X' : ''), 1, 0, 'C');
        $pdf->Cell(24, 6, ($donnees['avis']['ecole_ingenieur'] === 'favorable' ? 'X' : ''), 1, 0, 'C');
        $pdf->Cell(24, 6, ($donnees['avis']['ecole_ingenieur'] === 'assez_favorable' ? 'X' : ''), 1, 0, 'C');
        $pdf->Cell(24, 6, ($donnees['avis']['ecole_ingenieur'] === 'sans_avis' ? 'X' : ''), 1, 0, 'C');
        $pdf->Cell(24, 6, ($donnees['avis']['ecole_ingenieur'] === 'reserve' ? 'X' : ''), 1, 1, 'C');
        
        // Deuxième ligne Master - POSITION FIXE CORRIGÉE
        $pdf->SetXY(15, $pdf->GetY()); // Position X = 15 (marge de gauche) au lieu de 45
        $pdf->Cell(30, 6, '', 0, 0, 'C'); // Case vide pour aligner avec "Pour l'étudiant"
        $pdf->Cell(40, 6, 'En master', 1, 0, 'C');
        $pdf->Cell(24, 6, ($donnees['avis']['master'] === 'tres_favorable' ? 'X' : ''), 1, 0, 'C');
        $pdf->Cell(24, 6, ($donnees['avis']['master'] === 'favorable' ? 'X' : ''), 1, 0, 'C');
        $pdf->Cell(24, 6, ($donnees['avis']['master'] === 'assez_favorable' ? 'X' : ''), 1, 0, 'C');
        $pdf->Cell(24, 6, ($donnees['avis']['master'] === 'sans_avis' ? 'X' : ''), 1, 0, 'C');
        $pdf->Cell(24, 6, ($donnees['avis']['master'] === 'reserve' ? 'X' : ''), 1, 1, 'C');

        // "Nombre d'avis" - POSITION CORRIGÉE
        $pdf->SetFont('Arial', '', 6);
        
        // Sauvegarder la position Y courante
        $posY = $pdf->GetY();
        
        // Créer la case pour "Nombre d'avis"
        $pdf->Cell(30, 12, '', 1, 0, 'C'); // Case vide bordée
        
        // Écrire le texte centré dans la case
        $pdf->SetXY(15, $posY + 2); // Position centrée dans la case
        $pdf->Cell(30, 3, utf8_decode('Nombre d\'avis'), 0, 0, 'C');
        $pdf->SetXY(15, $posY + 5);
        $pdf->Cell(30, 3, 'pour la promotion', 0, 0, 'C');
        $pdf->SetXY(15, $posY + 8);
        $pdf->Cell(30, 3, '(total : ' . ($donnees['totalPromotion'] ?? '0') . ')', 0, 0, 'C');
        
        // REVENIR À LA POSITION CORRECTE pour continuer le tableau
        $pdf->SetXY(45, $posY); // Position X = 15 + 30 = 45
        $pdf->Cell(40, 6, utf8_decode('En école d\'ingénieurs'), 1, 0, 'C');
        $pdf->Cell(24, 6, $donnees['stats']['ecole_ingenieur']['tres_favorable'] ?? '0', 1, 0, 'C');
        $pdf->Cell(24, 6, $donnees['stats']['ecole_ingenieur']['favorable'] ?? '0', 1, 0, 'C');
        $pdf->Cell(24, 6, $donnees['stats']['ecole_ingenieur']['assez_favorable'] ?? '0', 1, 0, 'C');
        $pdf->Cell(24, 6, $donnees['stats']['ecole_ingenieur']['sans_avis'] ?? '0', 1, 0, 'C');
        $pdf->Cell(24, 6, $donnees['stats']['ecole_ingenieur']['reserve'] ?? '0', 1, 1, 'C');
        
        // Deuxième partie - Master - POSITION CORRIGÉE
        $pdf->SetXY(15, $posY + 6); // Position X = 15 (marge de gauche)
        $pdf->Cell(30, 6, '', 0, 0, 'C'); // Case vide pour aligner
        $pdf->Cell(40, 6, 'En master', 1, 0, 'C');
        $pdf->Cell(24, 6, $donnees['stats']['master']['tres_favorable'] ?? '0', 1, 0, 'C');
        $pdf->Cell(24, 6, $donnees['stats']['master']['favorable'] ?? '0', 1, 0, 'C');
        $pdf->Cell(24, 6, $donnees['stats']['master']['assez_favorable'] ?? '0', 1, 0, 'C');
        $pdf->Cell(24, 6, $donnees['stats']['master']['sans_avis'] ?? '0', 1, 0, 'C');
        $pdf->Cell(24, 6, $donnees['stats']['master']['reserve'] ?? '0', 1, 1, 'C');
        
        // Commentaire - UNE SEULE CASE + RESTE
        $pdf->SetFont('Arial', '', 6);
        $pdf->Cell(30, 10, 'Commentaire', 1, 0, 'C');
        $pdf->Cell(160, 10, utf8_decode(substr($donnees['avis']['commentaire'] ?? '', 0, 100)), 1, 1, 'L');

        // Signatures à droite
        $pdf->Ln(4);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetX(120);
        $pdf->Cell(70, 4, utf8_decode('Signature du Chef de Département'), 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 6);
        // Petit cadre nom
        $pdf->Rect(130, $pdf->GetY() + 2, 50, 8);
        $pdf->SetXY(130, $pdf->GetY() + 12);
        $pdf->Cell(50, 3, utf8_decode('Nom du chef de Dépt.'), 0, 1, 'C');
        
        // Grand cadre signature
        $pdf->Rect(130, $pdf->GetY() + 2, 50, 15);
        $pdf->SetXY(130, $pdf->GetY() + 19);
        $pdf->Cell(50, 3, utf8_decode('Signature et cachet du Dépt'), 0, 1, 'C');

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