document.addEventListener('DOMContentLoaded', function() {
    const btnModifier = document.getElementById('btnModifier');
    const btnGroup = document.getElementById('btnGroup');
    const btnAnnuler = document.getElementById('btnAnnuler');
    const btnValider = document.getElementById('btnValider');
    const boutonFlottant = document.getElementById('floatingBtn');
    const menuFlottant = document.getElementById('floatingMenu');

    // Gestion des boutons
    btnModifier.addEventListener('click', function() {
        const idEtudiant = document.getElementById('nomEtudiant').value;
        if (!idEtudiant) {
            alert('Veuillez sélectionner un étudiant avant de modifier');
            return;
        }
        toggleModeEdition(true);
    });

    btnAnnuler.addEventListener('click', () => toggleModeEdition(false, true));
    btnValider.addEventListener('click', () => {
        enregistrerModifications();
        toggleModeEdition(false);
    });

    // Menu flottant
    boutonFlottant.addEventListener('click', () => {
        menuFlottant.style.display = menuFlottant.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', function(e) {
        if (!boutonFlottant.contains(e.target) && !menuFlottant.contains(e.target)) {
            menuFlottant.style.display = 'none';
        }
    });

    remplirAnneesImport();
});

let tabEtudiants = [];
let valeurs_originales = {};

// Gestion des changements d'année
document.getElementById('anneePromotion').addEventListener('change', function() {
    const annee = this.value;
    const selectEtu = document.getElementById('nomEtudiant');

    selectEtu.innerHTML = '<option value="">Sélectionner un étudiant</option>';
    viderToutesDonnees(); // Cette fonction vide tout y compris les stats
    tabEtudiants = [];

    if (annee) {
        fetch('/api/etudiants/' + annee)
            .then(response => response.json())
            .then(data => {
                tabEtudiants = data;
                data.forEach(etudiant => {
                    const option = document.createElement('option');
                    option.value = etudiant.idEtudiant;
                    option.textContent = `${etudiant.nomEtudiant} ${etudiant.prenomEtudiant}`;
                    selectEtu.appendChild(option);
                });
                document.getElementById('nbAvisPromo').textContent = data.length;
                // Charger les vraies statistiques de la promotion
                actualiserStatistiquesAvis();
            })
            .catch(error => console.error('Erreur:', error));
    } else {
        // Si aucune année, remettre à zéro
        document.getElementById('nbAvisPromo').textContent = '0';
    }
});

// Gestion des étudiants
document.getElementById('nomEtudiant').addEventListener('change', function() {
    const id = this.value;
    const option = this.selectedOptions[0];
    const etudiant = tabEtudiants.find(e => e.idEtudiant === id);
    
    document.getElementById('ficheNomPrenom').textContent = option && id ? option.textContent : '';

    if (etudiant) {
        afficherInfosEtudiant(etudiant);
        chargerDonneesEtudiant(id);
    } else {
        // Utiliser une nouvelle fonction qui ne touche pas aux statistiques
        viderDonneesEtudiantSeul();
    }
});

// Nouvelle fonction pour vider seulement les données de l'étudiant
function viderDonneesEtudiantSeul() {
    const champsVide = ['ficheNomPrenom', 'parcours_n2', 'parcours_n1', 'parcours_n', 'apprentissage_but1', 
                        'apprentissage_but2', 'apprentissage_but3', 'parcours_but', 'mobilite_etranger', 
                        'abs_but1', 'abs_but2', 'abs_but3'];
    
    champsVide.forEach(champ => {
        const element = document.getElementById(champ);
        if (element) element.textContent = '';
    });
    
    viderCompetences();
    
    // Réinitialiser seulement les avis individuels de l'étudiant
    document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => radio.checked = false);
    document.getElementById('commentaireAvis').value = '';
    
    // NE PAS toucher aux statistiques de promotion - elles restent affichées
}

// Garder viderToutesDonnees() pour le changement d'année seulement
function viderToutesDonnees() {
    const champsVide = ['ficheNomPrenom', 'parcours_n2', 'parcours_n1', 'parcours_n', 'apprentissage_but1', 
                        'apprentissage_but2', 'apprentissage_but3', 'parcours_but', 'mobilite_etranger', 
                        'abs_but1', 'abs_but2', 'abs_but3'];
    
    champsVide.forEach(champ => {
        const element = document.getElementById(champ);
        if (element) element.textContent = '';
    });
    
    viderCompetences();
    
    document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => radio.checked = false);
    document.getElementById('commentaireAvis').value = '';
    
    // Remettre les statistiques à zéro seulement lors du changement d'année
    const statsIds = ['stats_ecole_tres_favorable', 'stats_ecole_favorable', 'stats_ecole_assez_favorable', 
                      'stats_ecole_sans_avis', 'stats_ecole_reserve', 'stats_master_tres_favorable', 
                      'stats_master_favorable', 'stats_master_assez_favorable', 'stats_master_sans_avis', 'stats_master_reserve'];
    
    statsIds.forEach(id => {
        const element = document.getElementById(id);
        if (element) element.textContent = '0';
    });
}

function afficherInfosEtudiant(etudiant) {
    const parcours = etudiant.parcoursEtudes ?etudiant.parcoursEtudes.replace(/\s+/g, '') : '';
    const mapBut = { 'S1S2': 'BUT 1', 'S3S4': 'BUT 2', 'S5S6': 'BUT 3' };
    
    document.getElementById('parcours_n2').textContent = mapBut[parcours.slice(-12, -8)] || parcours.slice(-12, -8);
    document.getElementById('parcours_n1').textContent = mapBut[parcours.slice(-8, -4)] || parcours.slice(-8, -4);
    document.getElementById('parcours_n').textContent = mapBut[parcours.slice(-4)] || parcours.slice(-4);
    document.getElementById('parcours_but').textContent = etudiant.parcoursBUT || '';
    document.getElementById('mobilite_etranger').textContent = etudiant.mobiliteEtranger || '';
}

function chargerDonneesEtudiant(id) {
    const endpoints = [
        { url: `/api/absences/${id}`, handler: afficherAbsences },
        { url: `/api/apprentissage/${id}`, handler: afficherApprentissage },
        { url: `/api/competences/${id}`, handler: afficherCompetences },
        { url: `/api/ressources/${id}`, handler: afficherRessources },
        { url: `/api/avis/etudiant/${id}`, handler: afficherAvis }
    ];

    endpoints.forEach(({ url, handler }) => {
        fetch(url)
            .then(response => response.json())
            .then(handler)
            .catch(error => console.error('Erreur:', error));
    });

    // Désactiver les champs par défaut
    setTimeout(() => {
        document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => radio.disabled = true);
        document.getElementById('commentaireAvis').disabled = true;
    }, 100);
}

function afficherAbsences(absences) {
    document.getElementById('abs_but1').textContent = absences.but1;
    document.getElementById('abs_but2').textContent = absences.but2;
    document.getElementById('abs_but3').textContent = absences.but3;
}

function afficherApprentissage(apprentissage) {
    document.getElementById('apprentissage_but1').textContent = apprentissage.but1;
    document.getElementById('apprentissage_but2').textContent = apprentissage.but2;
    document.getElementById('apprentissage_but3').textContent = apprentissage.but3;
}

function afficherAvis(avis) {
    document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => radio.checked = false);
    
    if (avis.ecole_ingenieur) {
        const radioEcole = document.querySelector(`input[name="avis_ecole_ingenieur"][value="${avis.ecole_ingenieur}"]`);
        if (radioEcole) radioEcole.checked = true;
    }
    
    if (avis.master) {
        const radioMaster = document.querySelector(`input[name="avis_master"][value="${avis.master}"]`);
        if (radioMaster) radioMaster.checked = true;
    }
    
    document.getElementById('commentaireAvis').value = avis.commentaire || '';
}

function viderCompetences() {
    const ids = [
        'BIN1_but1_moy', 'BIN1_but1_rang', 'BIN1_but2_moy', 'BIN1_but2_rang', 'BIN1_but3_moy', 'BIN1_but3_rang',
        'BIN2_but1_moy', 'BIN2_but1_rang', 'BIN2_but2_moy', 'BIN2_but2_rang', 'BIN2_but3_moy', 'BIN2_but3_rang',
        'BIN3_but1_moy', 'BIN3_but1_rang', 'BIN3_but2_moy', 'BIN3_but2_rang',
        'BIN4_but1_moy', 'BIN4_but1_rang', 'BIN4_but2_moy', 'BIN4_but2_rang',
        'BIN5_but1_moy', 'BIN5_but1_rang', 'BIN5_but2_moy', 'BIN5_but2_rang',
        'BIN6_but1_moy', 'BIN6_but1_rang', 'BIN6_but2_moy', 'BIN6_but2_rang', 'BIN6_but3_moy', 'BIN6_but3_rang',
        'maths_but1_moy', 'maths_but1_rang', 'maths_but2_moy', 'maths_but2_rang', 'maths_but3_moy', 'maths_but3_rang',
        'anglais_but1_moy', 'anglais_but1_rang', 'anglais_but2_moy', 'anglais_but2_rang'
    ];

    ids.forEach(id => {
        const element = document.getElementById(id);
        if (element) element.textContent = '';
    });
}

function afficherCompetences(competences) {
    viderCompetences();
    const moyennesParBUT = {};

    competences.forEach(comp => {
        const semestre = parseInt(comp.numeroSemestre);
        const numeroCompetence = parseInt(comp.codeCompetence.slice(-1));
        const moyenne = parseFloat(comp.moyenneCompetence);
        const rang = parseInt(comp.rangCompetence);

        if (semestre === 6) return;

        let but;
        if (semestre <= 2) but = 1;
        else if (semestre <= 4) but = 2;
        else if (semestre === 5) but = 3;

        const cle = `BIN${numeroCompetence}_but${but}`;

        if (!moyennesParBUT[cle]) {
            moyennesParBUT[cle] = { moyennes: [], rangs: [] };
        }

        moyennesParBUT[cle].moyennes.push(moyenne);
        moyennesParBUT[cle].rangs.push(rang);
    });

    for (let cle in moyennesParBUT) {
        const donnees = moyennesParBUT[cle];
        const moyenneFinale = cle.includes('_but3') ? 
            donnees.moyennes[0].toFixed(2) : 
            (donnees.moyennes.reduce((a, b) => a + b, 0) / donnees.moyennes.length).toFixed(2);
        const rangFinal = cle.includes('_but3') ? 
            donnees.rangs[0] : 
            Math.round(donnees.rangs.reduce((a, b) => a + b, 0) / donnees.rangs.length);

        document.getElementById(cle + '_moy').textContent = moyenneFinale;
        document.getElementById(cle + '_rang').textContent = rangFinal;
    }
}

function afficherRessources(ressources) {
    const moyennesParBUT = {};

    ressources.forEach(res => {
        const semestre = parseInt(res.numeroSemestre);
        const codeRes = res.codeRessource;
        const moyenne = parseFloat(res.moyenneRessource);
        const rang = parseInt(res.rangRessource);

        if (semestre === 6) return;

        let but;
        if (semestre <= 2) but = 1;
        else if (semestre <= 4) but = 2;
        else if (semestre === 5) but = 3;

        let matiere;
        if (['BINR106', 'BINR107', 'BINR207', 'BINR208', 'BINR209', 'BINR308', 'BINR309', 'BINR404', 'BINR511', 'BINR512'].includes(codeRes)) {
            matiere = 'maths';
        } else if (['BINR110', 'BINR212', 'BINR312', 'BINR412'].includes(codeRes)) {
            // Suppression de BINR514 car pas d'anglais en BUT 3 (S5)
            matiere = 'anglais';
        }

        if (!matiere) return;

        const cle = `${matiere}_but${but}`;

        if (!moyennesParBUT[cle]) {
            moyennesParBUT[cle] = { moyennes: [], rangs: [] };
        }

        moyennesParBUT[cle].moyennes.push(moyenne);
        moyennesParBUT[cle].rangs.push(rang);
    });

    for (let cle in moyennesParBUT) {
        const donnees = moyennesParBUT[cle];
        const moyenneFinale = (donnees.moyennes.reduce((a, b) => a + b, 0) / donnees.moyennes.length).toFixed(2);
        const rangMoyen = Math.round(donnees.rangs.reduce((a, b) => a + b, 0) / donnees.rangs.length);

        document.getElementById(cle + '_moy').textContent = moyenneFinale;
        document.getElementById(cle + '_rang').textContent = rangMoyen;
    }
}

function toggleModeEdition(activer, annuler = false) {
    const idEtudiant = document.getElementById('nomEtudiant').value;
    
    if (activer) {
        document.getElementById('btnModifier').classList.add('d-none');
        document.getElementById('btnGroup').classList.remove('d-none');
        
        // Sauvegarder valeurs originales
        valeurs_originales = {
            apprentissage_but3: document.getElementById('apprentissage_but3').textContent,
            mobilite_etranger: document.getElementById('mobilite_etranger').textContent,
            commentaire: document.getElementById('commentaireAvis').value,
            avis_ecole: document.querySelector('input[name="avis_ecole_ingenieur"]:checked')?.value || '',
            avis_master: document.querySelector('input[name="avis_master"]:checked')?.value || ''
        };
        
        activerChamps();
    } else {
        document.getElementById('btnGroup').classList.add('d-none');
        document.getElementById('btnModifier').classList.remove('d-none');
        
        if (annuler) {
            restaurerValeurs();
        }
        
        desactiverChamps();
    }
}

function activerChamps() {
    document.getElementById('apprentissage_but3').classList.add('d-none');
    document.getElementById('apprentissage_but3_edit').classList.remove('d-none');
    document.getElementById('apprentissage_but3_edit').value = valeurs_originales.apprentissage_but3;
    
    document.getElementById('mobilite_etranger').classList.add('d-none');
    document.getElementById('mobilite_etranger_edit').classList.remove('d-none');
    document.getElementById('mobilite_etranger_edit').value = valeurs_originales.mobilite_etranger;
    
    // Ajouter validation en temps réel
    const apprentissageField = document.getElementById('apprentissage_but3_edit');
    const mobiliteField = document.getElementById('mobilite_etranger_edit');
    
    // Validation apprentissage (3 caractères max)
    apprentissageField.addEventListener('input', function() {
        if (this.value.length > 3) {
            this.value = this.value.substring(0, 3);
            this.style.borderColor = 'red';
            setTimeout(() => {
                this.style.borderColor = '';
            }, 1500);
        }
    });
    
    // Validation mobilité (80 caractères max)
    mobiliteField.addEventListener('input', function() {
        if (this.value.length > 80) {
            this.value = this.value.substring(0, 80);
            this.style.borderColor = 'red';
            setTimeout(() => {
                this.style.borderColor = '';
            }, 1500);
        }
    });
    
    document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => radio.disabled = false);
    document.getElementById('commentaireAvis').disabled = false;
}

function desactiverChamps() {
    document.getElementById('apprentissage_but3').classList.remove('d-none');
    document.getElementById('apprentissage_but3_edit').classList.add('d-none');
    
    document.getElementById('mobilite_etranger').classList.remove('d-none');
    document.getElementById('mobilite_etranger_edit').classList.add('d-none');
    
    document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => radio.disabled = true);
    document.getElementById('commentaireAvis').disabled = true;
}

function restaurerValeurs() {
    document.getElementById('apprentissage_but3').textContent = valeurs_originales.apprentissage_but3;
    document.getElementById('mobilite_etranger').textContent = valeurs_originales.mobilite_etranger;
    document.getElementById('commentaireAvis').value = valeurs_originales.commentaire;
    
    document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => radio.checked = false);
    
    if (valeurs_originales.avis_ecole) {
        const radioEcole = document.querySelector(`input[name="avis_ecole_ingenieur"][value="${valeurs_originales.avis_ecole}"]`);
        if (radioEcole) radioEcole.checked = true;
    }
    
    if (valeurs_originales.avis_master) {
        const radioMaster = document.querySelector(`input[name="avis_master"][value="${valeurs_originales.avis_master}"]`);
        if (radioMaster) radioMaster.checked = true;
    }
}

function enregistrerModifications() {
    const idEtudiant = document.getElementById('nomEtudiant').value;
    
    if (!idEtudiant) {
        alert('Erreur: aucun étudiant sélectionné');
        return;
    }
    
    const nouvelles_valeurs = {
        apprentissage_but3: document.getElementById('apprentissage_but3_edit').value,
        mobilite_etranger: document.getElementById('mobilite_etranger_edit').value,
        commentaire: document.getElementById('commentaireAvis').value,
        avis_ecole: document.querySelector('input[name="avis_ecole_ingenieur"]:checked')?.value || '',
        avis_master: document.querySelector('input[name="avis_master"]:checked')?.value || ''
    };
    
    // Validation des limites de caractères
    if (nouvelles_valeurs.apprentissage_but3.length > 3) {
        alert('Erreur: L\'apprentissage ne peut pas dépasser 3 caractères');
        return;
    }
    
    if (nouvelles_valeurs.mobilite_etranger.length > 80) {
        alert('Erreur: La mobilité à l\'étranger ne peut pas dépasser 80 caractères');
        return;
    }
    
    // Sauvegarder données
    sauvegarderApprentissageEtMobilite(idEtudiant, nouvelles_valeurs);
    
    if (nouvelles_valeurs.commentaire !== valeurs_originales.commentaire) {
        sauvegarderData('/api/avis/commentaire', { idEtudiant, commentaire: nouvelles_valeurs.commentaire });
    }
    
    if (nouvelles_valeurs.avis_ecole && nouvelles_valeurs.avis_ecole !== valeurs_originales.avis_ecole) {
        sauvegarderData('/api/avis/sauvegarder', { idEtudiant, typePoursuite: 'ecole_ingenieur', typeAvis: nouvelles_valeurs.avis_ecole });
    }
    
    if (nouvelles_valeurs.avis_master && nouvelles_valeurs.avis_master !== valeurs_originales.avis_master) {
        sauvegarderData('/api/avis/sauvegarder', { idEtudiant, typePoursuite: 'master', typeAvis: nouvelles_valeurs.avis_master });
    }
    
    // Mettre à jour affichage
    document.getElementById('apprentissage_but3').textContent = nouvelles_valeurs.apprentissage_but3;
    document.getElementById('mobilite_etranger').textContent = nouvelles_valeurs.mobilite_etranger;
}

function sauvegarderApprentissageEtMobilite(idEtudiant, valeurs) {
    sauvegarderData('/api/etudiant/modifier', {
        idEtudiant,
        apprentissage_but3: valeurs.apprentissage_but3,
        mobilite_etranger: valeurs.mobilite_etranger
    });
}

function sauvegarderData(url, data) {
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            console.log('Données sauvegardées');
            if (url.includes('avis')) {
                actualiserStatistiquesAvis();
            }
            // Forcer la mise à jour des données de l'étudiant après modification
            if (url.includes('etudiant/modifier')) {
                const idEtudiant = document.getElementById('nomEtudiant').value;
                if (idEtudiant) {
                    // Recharger les infos de l'étudiant depuis la base
                    const etudiantIndex = tabEtudiants.findIndex(e => e.idEtudiant === idEtudiant);
                    if (etudiantIndex !== -1) {
                        // Mettre à jour les données dans le tableau local
                        if (data.mobilite_etranger !== undefined) {
                            tabEtudiants[etudiantIndex].mobiliteEtranger = data.mobilite_etranger;
                        }
                    }
                }
            }
        } else {
            alert('Erreur: ' + (result.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion');
    });
}

function actualiserStatistiquesAvis() {
    const anneePromotion = document.getElementById('anneePromotion').value;
    if (!anneePromotion) return;
    
    fetch('/api/avis/stats/' + anneePromotion)
        .then(response => response.json())
        .then(stats => {
            const types = ['ecole', 'master'];
            const avis = ['tres_favorable', 'favorable', 'assez_favorable', 'sans_avis', 'reserve'];
            
            types.forEach(type => {
                avis.forEach(avisType => {
                    const key = type === 'ecole' ? 'ecole_ingenieur' : 'master';
                    document.getElementById(`stats_${type}_${avisType}`).textContent = stats[key][avisType];
                });
            });
            
            document.getElementById('nbAvisPromo').textContent = stats.totalAvisPromotion || 0;
        })
        .catch(error => console.error('Erreur:', error));
}

function viderToutesDonnees() {
    const champsVide = ['ficheNomPrenom', 'parcours_n2', 'parcours_n1', 'parcours_n', 'apprentissage_but1', 
                        'apprentissage_but2', 'apprentissage_but3', 'parcours_but', 'mobilite_etranger', 
                        'abs_but1', 'abs_but2', 'abs_but3'];
    
    champsVide.forEach(champ => {
        const element = document.getElementById(champ);
        if (element) element.textContent = '';
    });
    
    viderCompetences();
    
    document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => radio.checked = false);
    document.getElementById('commentaireAvis').value = '';
    
    // Remettre les statistiques à zéro seulement lors du changement d'année
    const statsIds = ['stats_ecole_tres_favorable', 'stats_ecole_favorable', 'stats_ecole_assez_favorable', 
                      'stats_ecole_sans_avis', 'stats_ecole_reserve', 'stats_master_tres_favorable', 
                      'stats_master_favorable', 'stats_master_assez_favorable', 'stats_master_sans_avis', 'stats_master_reserve'];
    
    statsIds.forEach(id => {
        const element = document.getElementById(id);
        if (element) element.textContent = '0';
    });
}

function remplirAnneesImport() {
    const selectAnneeImport = document.getElementById('anneeImport');
    if (!selectAnneeImport) return;
    
    const anneeActuelle = new Date().getFullYear();
    selectAnneeImport.innerHTML = '<option value="" selected>Année de la promotion</option>';
    
    for (let i = 0; i < 4; i++) {
        const annee = anneeActuelle - i;
        const option = document.createElement('option');
        option.value = annee;
        option.textContent = annee;
        selectAnneeImport.appendChild(option);
    }
}

// Validation import
document.getElementById('importForm').addEventListener('submit', function(e) {
    const fichiers = this.querySelector('input[type="file"]').files;
    
    if (fichiers.length === 0) {
        e.preventDefault();
        alert('Veuillez sélectionner au moins un fichier');
        return;
    }
    
    for (let i = 0; i < fichiers.length; i++) {
        const nomFichier = fichiers[i].name.toLowerCase();
        if (nomFichier.includes('s6') || nomFichier.includes('semestre6') || nomFichier.includes('sem6')) {
            e.preventDefault();
            alert('Les fichiers contenant des données du semestre 6 ne sont pas autorisés');
            return;
        }
    }
});

function exporterPDF() {
    const idEtudiant = document.getElementById('nomEtudiant').value;
    const anneePromotion = document.getElementById('anneePromotion').value;
    
    if (!idEtudiant) {
        alert('Veuillez sélectionner un étudiant');
        return;
    }

    const donneesPDF = {
        nomEtudiant: document.getElementById('ficheNomPrenom').textContent,
        anneePromotion: anneePromotion,
        parcoursBUT: document.getElementById('parcours_but').textContent,
        mobiliteEtranger: document.getElementById('mobilite_etranger').textContent || '',
        apprentissageBUT1: document.getElementById('apprentissage_but1').textContent,
        apprentissageBUT2: document.getElementById('apprentissage_but2').textContent,
        apprentissageBUT3: document.getElementById('apprentissage_but3').textContent,
        parcoursN2: document.getElementById('parcours_n2').textContent,
        parcoursN1: document.getElementById('parcours_n1').textContent,
        parcoursN: document.getElementById('parcours_n').textContent,
        absences: {
            but1: document.getElementById('abs_but1').textContent || '',
            but2: document.getElementById('abs_but2').textContent || '',
            but3: document.getElementById('abs_but3').textContent || ''
        },
        competences: {},
        ressources: {},
        avis: {
            ecole_ingenieur: document.querySelector('input[name="avis_ecole_ingenieur"]:checked')?.value || '',
            master: document.querySelector('input[name="avis_master"]:checked')?.value || '',
            commentaire: document.getElementById('commentaireAvis').value || ''
        },
        stats: {
            ecole_ingenieur: {
                tres_favorable: document.getElementById('stats_ecole_tres_favorable').textContent || '0',
                favorable: document.getElementById('stats_ecole_favorable').textContent || '0',
                assez_favorable: document.getElementById('stats_ecole_assez_favorable').textContent || '0',
                sans_avis: document.getElementById('stats_ecole_sans_avis').textContent || '0',
                reserve: document.getElementById('stats_ecole_reserve').textContent || '0'
            },
            master: {
                tres_favorable: document.getElementById('stats_master_tres_favorable').textContent || '0',
                favorable: document.getElementById('stats_master_favorable').textContent || '0',
                assez_favorable: document.getElementById('stats_master_assez_favorable').textContent || '0',
                sans_avis: document.getElementById('stats_master_sans_avis').textContent || '0',
                reserve: document.getElementById('stats_master_reserve').textContent || '0'
            },
            totalAvisPromotion: document.getElementById('nbAvisPromo').textContent || '0'
        }
    };
    
    // Récupérer compétences et ressources
    const competenceIds = [
        'BIN1_but1_moy', 'BIN1_but1_rang', 'BIN1_but2_moy', 'BIN1_but2_rang', 'BIN1_but3_moy', 'BIN1_but3_rang',
        'BIN2_but1_moy', 'BIN2_but1_rang', 'BIN2_but2_moy', 'BIN2_but2_rang', 'BIN2_but3_moy', 'BIN2_but3_rang',
        'BIN3_but1_moy', 'BIN3_but1_rang', 'BIN3_but2_moy', 'BIN3_but2_rang',
        'BIN4_but1_moy', 'BIN4_but1_rang', 'BIN4_but2_moy', 'BIN4_but2_rang',
        'BIN5_but1_moy', 'BIN5_but1_rang', 'BIN5_but2_moy', 'BIN5_but2_rang',
        'BIN6_but1_moy', 'BIN6_but1_rang', 'BIN6_but2_moy', 'BIN6_but2_rang', 'BIN6_but3_moy', 'BIN6_but3_rang'
    ];
    
    const ressourceIds = [
        'maths_but1_moy', 'maths_but1_rang', 'maths_but2_moy', 'maths_but2_rang', 'maths_but3_moy', 'maths_but3_rang',
        'anglais_but1_moy', 'anglais_but1_rang', 'anglais_but2_moy', 'anglais_but2_rang'
    ];
    
    [...competenceIds, ...ressourceIds].forEach(id => {
        const element = document.getElementById(id);
        const key = competenceIds.includes(id) ? 'competences' : 'ressources';
        if (element) {
            donneesPDF[key][id] = element.textContent || '';
        }
    });
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/export/pdf/' + idEtudiant;
    form.target = '_blank';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'donneesPDF';
    input.value = JSON.stringify(donneesPDF);
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    document.getElementById('floatingMenu').style.display = 'none';
}