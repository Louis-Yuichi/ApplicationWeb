document.addEventListener('DOMContentLoaded', function()
{
    const btnModifier = document.getElementById('btnModifier');
    const btnGroup    = document.getElementById('btnGroup'   );
    const btnAnnuler  = document.getElementById('btnAnnuler' );
    const btnValider  = document.getElementById('btnValider' );

    btnModifier.addEventListener('click', function()
    {
        const idEtudiant = document.getElementById('nomEtudiant').value;
        
        if (!idEtudiant) {
            alert('Veuillez sélectionner un étudiant avant de modifier');
            return; // Annuler l'action
        }
        
        btnModifier.classList.add('d-none');
        btnGroup.classList.remove('d-none');
        
        // Activer le mode édition
        activerModeEdition();
    });

    btnAnnuler.addEventListener('click', function()
    {
        btnGroup.classList.add('d-none');
        btnModifier.classList.remove('d-none');
        
        // Annuler les modifications
        annulerModifications();
    });

    btnValider.addEventListener('click', function()
    {
        btnGroup.classList.add('d-none');
        btnModifier.classList.remove('d-none');
        
        // Enregistrer les modifications
        enregistrerModifications();
    });

    const boutonFlottant = document.getElementById('floatingBtn' );
    const menuFlottant   = document.getElementById('floatingMenu');

    boutonFlottant.addEventListener('click', function()
    {
        menuFlottant.style.display = menuFlottant.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', function(evenement)
    {
        if (!boutonFlottant.contains(evenement.target) && !menuFlottant.contains(evenement.target))
        {
            menuFlottant.style.display = 'none';
        }
    });

    // Remplir le select d'import avec les 4 dernières années (actuelle + 3 précédentes)
    remplirAnneesImport();
});

let tabEtudiants = [];

// Variables pour stocker les valeurs originales
let valeurs_originales = {};

document.getElementById('anneePromotion').addEventListener('change', function()
{
    const annee      = this.value;
    const selectEtu  = document.getElementById('nomEtudiant');
    const champsVide = ['ficheNomPrenom', 'parcours_n2', 'parcours_n1', 'parcours_n', 'apprentissage_but1', 
                        'apprentissage_but2', 'apprentissage_but3', 'parcours_but', 'mobilite_etranger', 
                        'abs_but1', 'abs_but2', 'abs_but3'];

    selectEtu.innerHTML = '<option value="">Sélectionner un étudiant</option>';

    champsVide.forEach(champ => document.getElementById(champ).textContent = '');
    document.getElementById('nbAvisPromo').textContent = '0';
    
    // Vider aussi les compétences quand on change d'année
    viderCompetences();

    tabEtudiants = [];

    if (annee)
    {
        fetch('/api/etudiants/' + annee).then(reponse => reponse.json()).then(data =>
        {
            tabEtudiants = data;
            data.forEach(etudiant =>
            {
                const option = document.createElement('option');
                option.value = etudiant.idEtudiant;
                option.textContent = `${etudiant.nomEtudiant} ${etudiant.prenomEtudiant}`;
                selectEtu.appendChild(option);
            });

            document.getElementById('nbAvisPromo').textContent = data.length;
        }).catch(error => console.error('Erreur lors du chargement des étudiants:', error));
    }
});

document.getElementById('nomEtudiant').addEventListener('change', function()
{
    const id       = this.value;
    const option   = this.selectedOptions[0];
    const etudiant = tabEtudiants.find(e => e.idEtudiant === id);
    
    document.getElementById('ficheNomPrenom').textContent = option && id ? option.textContent : '';

    if (etudiant)
    {
        const parcours = etudiant.parcoursEtudes ?etudiant.parcoursEtudes.replace(/\s+/g, '') : '';
        const mapBut   = { 'S1S2': 'BUT 1', 'S3S4': 'BUT 2', 'S5S6': 'BUT 3' };
        
        const n2 = parcours.slice(-12, -8);
        const n1 = parcours.slice(-8, -4);
        const n  = parcours.slice(-4);

        document.getElementById('parcours_n2'       ).textContent = mapBut[n2] || n2;
        document.getElementById('parcours_n1'       ).textContent = mapBut[n1] || n1;
        document.getElementById('parcours_n'        ).textContent = mapBut[n]  || n;
        document.getElementById('parcours_but'      ).textContent = etudiant.parcoursBUT      || '';
        document.getElementById('mobilite_etranger' ).textContent = etudiant.mobiliteEtranger || '';
    }
    else
    {
        const champsEtudiant = ['parcours_n2', 'parcours_n1', 'parcours_n', 'parcours_but', 'mobilite_etranger'];
        champsEtudiant.forEach(champ => document.getElementById(champ).textContent = '');
    }

    if (id)
    {
        console.log('Chargement des données pour étudiant:', id);
        
        // Charger absences
        fetch('/api/absences/' + id)
            .then(reponse => reponse.json())
            .then(absences => {
                console.log('Absences reçues:', absences);
                document.getElementById('abs_but1').textContent = absences.but1;
                document.getElementById('abs_but2').textContent = absences.but2;
                document.getElementById('abs_but3').textContent = absences.but3;
            })
            .catch(error => console.error('Erreur absences:', error));

        // Charger apprentissage
        fetch('/api/apprentissage/' + id)
            .then(reponse => reponse.json())
            .then(apprentissage => {
                console.log('Apprentissage reçu:', apprentissage);
                document.getElementById('apprentissage_but1').textContent = apprentissage.but1;
                document.getElementById('apprentissage_but2').textContent = apprentissage.but2;
                document.getElementById('apprentissage_but3').textContent = apprentissage.but3;
            })
            .catch(error => console.error('Erreur apprentissage:', error));

        // Charger compétences
        fetch('/api/competences/' + id)
            .then(reponse => reponse.json())
            .then(competences => {
                console.log('Compétences reçues:', competences);
                viderCompetences();
                afficherCompetences(competences);
            })
            .catch(error => console.error('Erreur compétences:', error));

        // Charger ressources
        fetch('/api/ressources/' + id)
            .then(reponse => reponse.json())
            .then(ressources => {
                console.log('Ressources reçues:', ressources);
                afficherRessources(ressources);
            })
            .catch(error => console.error('Erreur ressources:', error));

        // Charger les avis de l'étudiant
        fetch('/api/avis/etudiant/' + id)
            .then(reponse => reponse.json())
            .then(avis => {
                console.log('Avis reçus:', avis);
                
                // Vider tous les radio buttons d'abord
                document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => {
                    radio.checked = false;
                });
                
                // Cocher les avis appropriés
                if (avis.ecole_ingenieur) {
                    const radioEcole = document.querySelector(`input[name="avis_ecole_ingenieur"][value="${avis.ecole_ingenieur}"]`);
                    if (radioEcole) radioEcole.checked = true;
                }
                
                if (avis.master) {
                    const radioMaster = document.querySelector(`input[name="avis_master"][value="${avis.master}"]`);
                    if (radioMaster) radioMaster.checked = true;
                }
                
                // Remplir le commentaire
                const commentaireTextarea = document.getElementById('commentaireAvis');
                if (commentaireTextarea) {
                    commentaireTextarea.value = avis.commentaire || '';
                }
            })
            .catch(error => console.error('Erreur avis:', error));
            
        // DÉSACTIVER LES CHAMPS PAR DÉFAUT
        setTimeout(() => {
            document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => {
                radio.disabled = true;
            });
            
            document.getElementById('commentaireAvis').disabled = true;
        }, 100);
    }
    else
    {
        ['abs_but1', 'abs_but2', 'abs_but3', 'apprentissage_but1', 'apprentissage_but2', 'apprentissage_but3'].forEach(
            champ => document.getElementById(champ).textContent = ''
        );

        viderCompetences();
        
        // Vider les avis si aucun étudiant sélectionné
        document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => {
            radio.checked = false;
        });
        
        const commentaireTextarea = document.getElementById('commentaireAvis');
        if (commentaireTextarea) {
            commentaireTextarea.value = '';
        }
    }
});

function viderCompetences()
{
	const ids =
	[
		'BIN1_but1_moy'   , 'BIN1_but1_rang'   , 'BIN1_but2_moy'   , 'BIN1_but2_rang' , 'BIN1_but3_moy', 'BIN1_but3_rang',
		'BIN2_but1_moy'   , 'BIN2_but1_rang'   , 'BIN2_but2_moy'   , 'BIN2_but2_rang' , 'BIN2_but3_moy', 'BIN2_but3_rang',
		'BIN3_but1_moy'   , 'BIN3_but1_rang'   , 'BIN3_but2_moy'   , 'BIN3_but2_rang' ,
		'BIN4_but1_moy'   , 'BIN4_but1_rang'   , 'BIN4_but2_moy'   , 'BIN4_but2_rang' ,
		'BIN5_but1_moy'   , 'BIN5_but1_rang'   , 'BIN5_but2_moy'   , 'BIN5_but2_rang' ,
		'BIN6_but1_moy'   , 'BIN6_but1_rang'   , 'BIN6_but2_moy'   , 'BIN6_but2_rang' , 'BIN6_but3_moy', 'BIN6_but3_rang',
		'maths_but1_moy'  , 'maths_but1_rang'  , 'maths_but2_moy'  , 'maths_but2_rang', 'maths_but3_moy', 'maths_but3_rang',
		'anglais_but1_moy', 'anglais_but1_rang' , 'anglais_but2_moy' , 'anglais_but2_rang'
	];

	ids.forEach(id =>
	{
		const element = document.getElementById(id);
		if (element) element.textContent = '';
	});
}

function afficherCompetences(competences)
{
	const moyennesParBUT = {};

	competences.forEach(comp =>
	{
		const semestre         = parseInt(comp.numeroSemestre);
		const codeComp         = comp.codeCompetence;
		const moyenne          = parseFloat(comp.moyenneCompetence);
		const rang             = parseInt(comp.rangCompetence);
		const numeroCompetence = parseInt(codeComp.slice(-1));

		let but;
		if      (semestre <=  2) but = 1;
		else if (semestre <=  4) but = 2;
		else if (semestre === 5) but = 3;

		if (semestre === 6) return;

		const cle = `BIN${numeroCompetence}_but${but}`;

		if (!moyennesParBUT[cle])
		{
			moyennesParBUT[cle] = { moyennes: [], rangs: [] };
		}

		moyennesParBUT[cle].moyennes.push(moyenne);
		moyennesParBUT[cle].rangs.push(rang);
	});

	for (let cle in moyennesParBUT)
	{
		const donnees = moyennesParBUT[cle];

		if (cle.includes('_but3'))
		{

			const moyenneFinale = donnees.moyennes[0].toFixed(2);
			const rangFinal     = donnees.rangs[0];
			
			document.getElementById(cle + '_moy' ).textContent = moyenneFinale;
			document.getElementById(cle + '_rang').textContent = rangFinal;
		}
		else
		{
			const moyenneFinale = (donnees.moyennes.reduce((a, b) => a + b, 0) / donnees.moyennes.length).toFixed(2);
			const rangMoyen     = Math.round(donnees.rangs.reduce((a, b) => a + b, 0) / donnees.rangs.length);

			document.getElementById(cle + '_moy' ).textContent = moyenneFinale;
			document.getElementById(cle + '_rang').textContent = rangMoyen;
		}
	}
}

function afficherRessources(ressources)
{
    const moyennesParBUT = {};

    ressources.forEach(res =>
    {
        const semestre = parseInt(res.numeroSemestre);
        const codeRes  = res.codeRessource;
        const moyenne  = parseFloat(res.moyenneRessource);
        const rang     = parseInt(res.rangRessource);

        let but;
        if      (semestre <= 2) but = 1;
        else if (semestre <= 4) but = 2;
        else if (semestre === 5) but = 3;

        if (semestre === 6) return;

        // Mapping des codes ressources vers les matières - CORRIGÉ
        let matiere;
        if (['BINR106', 'BINR107', 'BINR207', 'BINR208', 'BINR209', 'BINR308', 'BINR309', 'BINR404', 'BINR511', 'BINR512'].includes(codeRes)) {
            matiere = 'maths';
        } else if (['BINR110', 'BINR212', 'BINR312', 'BINR412', 'BINR514'].includes(codeRes)) {
            matiere = 'anglais';
        }

        if (!matiere) return;

        const cle = `${matiere}_but${but}`;

        if (!moyennesParBUT[cle])
        {
            moyennesParBUT[cle] = { moyennes: [], rangs: [] };
        }

        moyennesParBUT[cle].moyennes.push(moyenne);
        moyennesParBUT[cle].rangs.push(rang);
    });

    for (let cle in moyennesParBUT)
    {
        const donnees = moyennesParBUT[cle];

        if (cle.includes('_but3'))
        {
            // Pour BUT 3, on fait la moyenne des ressources du S5
            const moyenneFinale = (donnees.moyennes.reduce((a, b) => a + b, 0) / donnees.moyennes.length).toFixed(2);
            const rangMoyen = Math.round(donnees.rangs.reduce((a, b) => a + b, 0) / donnees.rangs.length);
            
            document.getElementById(cle + '_moy' ).textContent = moyenneFinale;
            document.getElementById(cle + '_rang').textContent = rangMoyen;
        }
        else
        {
            // Pour BUT 1 et BUT 2, on fait la moyenne des ressources
            const moyenneFinale = (donnees.moyennes.reduce((a, b) => a + b, 0) / donnees.moyennes.length).toFixed(2);
            const rangMoyen     = Math.round(donnees.rangs.reduce((a, b) => a + b, 0) / donnees.rangs.length);

            document.getElementById(cle + '_moy' ).textContent = moyenneFinale;
            document.getElementById(cle + '_rang').textContent = rangMoyen;
        }
    }
}

// Gestionnaire pour les changements d'avis
document.addEventListener('change', function(e) {
    if (e.target.type === 'radio' && e.target.name.startsWith('avis_')) {
        const idEtudiant = document.getElementById('nomEtudiant').value;
        
        if (!idEtudiant) {
            alert('Veuillez sélectionner un étudiant');
            e.target.checked = false;
            return;
        }
        
        const typePoursuite = e.target.dataset.type;
        const typeAvis = e.target.value;
        
        sauvegarderAvis(idEtudiant, typePoursuite, typeAvis);
    }
});

function sauvegarderAvis(idEtudiant, typePoursuite, typeAvis) {
    fetch('/api/avis/sauvegarder', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            idEtudiant: idEtudiant,
            typePoursuite: typePoursuite,
            typeAvis: typeAvis
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Avis sauvegardé:', typeAvis);
            actualiserStatistiquesAvis();
        } else {
            alert('Erreur lors de la sauvegarde: ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion');
    });
}

// Gestionnaire pour le commentaire
document.getElementById('commentaireAvis').addEventListener('blur', function() {
    const idEtudiant = document.getElementById('nomEtudiant').value;
    
    if (!idEtudiant) return;
    
    const commentaire = this.value.trim();
    sauvegarderCommentaire(idEtudiant, commentaire);
});

function sauvegarderCommentaire(idEtudiant, commentaire) {
    fetch('/api/avis/commentaire', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            idEtudiant: idEtudiant,
            commentaire: commentaire
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Commentaire sauvegardé');
        } else {
            alert('Erreur lors de la sauvegarde du commentaire: ' + (data.message || 'Erreur inconnue'));
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
            console.log('Statistiques avis:', stats);
            
            // Mettre à jour les statistiques École d'ingénieurs
            document.getElementById('stats_ecole_tres_favorable').textContent = stats.ecole_ingenieur.tres_favorable;
            document.getElementById('stats_ecole_favorable').textContent = stats.ecole_ingenieur.favorable;
            document.getElementById('stats_ecole_assez_favorable').textContent = stats.ecole_ingenieur.assez_favorable;
            document.getElementById('stats_ecole_sans_avis').textContent = stats.ecole_ingenieur.sans_avis;
            document.getElementById('stats_ecole_reserve').textContent = stats.ecole_ingenieur.reserve;
            
            // Mettre à jour les statistiques Master
            document.getElementById('stats_master_tres_favorable').textContent = stats.master.tres_favorable;
            document.getElementById('stats_master_favorable').textContent = stats.master.favorable;
            document.getElementById('stats_master_assez_favorable').textContent = stats.master.assez_favorable;
            document.getElementById('stats_master_sans_avis').textContent = stats.master.sans_avis;
            document.getElementById('stats_master_reserve').textContent = stats.master.reserve;
            
            // NOUVEAU : Mettre à jour le nombre total d'avis de la promotion
            const totalAvis = stats.totalAvisPromotion || 0;
            document.getElementById('nbAvisPromo').textContent = totalAvis;
        })
        .catch(error => console.error('Erreur statistiques:', error));
}

// Charger les avis existants quand on sélectionne un étudiant
document.getElementById('nomEtudiant').addEventListener('change', function()
{
    const id       = this.value;
    const option   = this.selectedOptions[0];
    const etudiant = tabEtudiants.find(e => e.idEtudiant === id);
    
    document.getElementById('ficheNomPrenom').textContent = option && id ? option.textContent : '';

    if (etudiant)
    {
        const parcours = etudiant.parcoursEtudes ?etudiant.parcoursEtudes.replace(/\s+/g, '') : '';
        const mapBut   = { 'S1S2': 'BUT 1', 'S3S4': 'BUT 2', 'S5S6': 'BUT 3' };
        
        const n2 = parcours.slice(-12, -8);
        const n1 = parcours.slice(-8, -4);
        const n  = parcours.slice(-4);

        document.getElementById('parcours_n2'       ).textContent = mapBut[n2] || n2;
        document.getElementById('parcours_n1'       ).textContent = mapBut[n1] || n1;
        document.getElementById('parcours_n'        ).textContent = mapBut[n]  || n;
        document.getElementById('parcours_but'      ).textContent = etudiant.parcoursBUT      || '';
        document.getElementById('mobilite_etranger' ).textContent = etudiant.mobiliteEtranger || '';
    }
    else
    {
        const champsEtudiant = ['parcours_n2', 'parcours_n1', 'parcours_n', 'parcours_but', 'mobilite_etranger'];
        champsEtudiant.forEach(champ => document.getElementById(champ).textContent = '');
    }

    if (id)
    {
        console.log('Chargement des données pour étudiant:', id);
        
        fetch('/api/absences/' + id)
            .then(reponse => reponse.json())
            .then(absences => {
                console.log('Absences reçues:', absences);
                document.getElementById('abs_but1').textContent = absences.but1;
                document.getElementById('abs_but2').textContent = absences.but2;
                document.getElementById('abs_but3').textContent = absences.but3;
            })
            .catch(error => console.error('Erreur absences:', error));

        fetch('/api/apprentissage/' + id)
            .then(reponse => reponse.json())
            .then(apprentissage => {
                console.log('Apprentissage reçu:', apprentissage);
                document.getElementById('apprentissage_but1').textContent = apprentissage.but1;
                document.getElementById('apprentissage_but2').textContent = apprentissage.but2;
                document.getElementById('apprentissage_but3').textContent = apprentissage.but3;
            })
            .catch(error => console.error('Erreur apprentissage:', error));

        fetch('/api/competences/' + id)
            .then(reponse => reponse.json())
            .then(competences => {
                console.log('Compétences reçues:', competences);
                viderCompetences();
                afficherCompetences(competences);
            })
            .catch(error => console.error('Erreur compétences:', error));

        fetch('/api/ressources/' + id)
            .then(reponse => reponse.json())
            .then(ressources => {
                console.log('Ressources reçues:', ressources);
                afficherRessources(ressources);
            })
            .catch(error => console.error('Erreur ressources:', error));

        // Charger les avis de l'étudiant
        fetch('/api/avis/etudiant/' + id)
            .then(reponse => reponse.json())
            .then(avis => {
                console.log('Avis reçus:', avis);
                
                // Vider tous les radio buttons d'abord
                document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => {
                    radio.checked = false;
                });
                
                // Cocher les avis appropriés
                if (avis.ecole_ingenieur) {
                    const radioEcole = document.querySelector(`input[name="avis_ecole_ingenieur"][value="${avis.ecole_ingenieur}"]`);
                    if (radioEcole) radioEcole.checked = true;
                }
                
                if (avis.master) {
                    const radioMaster = document.querySelector(`input[name="avis_master"][value="${avis.master}"]`);
                    if (radioMaster) radioMaster.checked = true;
                }
                
                // Remplir le commentaire
                const commentaireTextarea = document.getElementById('commentaireAvis');
                if (commentaireTextarea) {
                    commentaireTextarea.value = avis.commentaire || '';
                }
            })
            .catch(error => console.error('Erreur avis:', error));
    }
    else
    {
        ['abs_but1', 'abs_but2', 'abs_but3', 'apprentissage_but1', 'apprentissage_but2', 'apprentissage_but3'].forEach
        (
            champ => document.getElementById(champ).textContent = ''
        );

        viderCompetences();
        
        // Vider les avis si aucun étudiant sélectionné
        document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => {
            radio.checked = false;
        });
        
        const commentaireTextarea = document.getElementById('commentaireAvis');
        if (commentaireTextarea) {
            commentaireTextarea.value = '';
        }
    }
});

function viderToutesDonnees() {
    // Vider les champs étudiants
    const champsVide = ['ficheNomPrenom', 'parcours_n2', 'parcours_n1', 'parcours_n', 'apprentissage_but1', 
                        'apprentissage_but2', 'apprentissage_but3', 'parcours_but', 'mobilite_etranger', 
                        'abs_but1', 'abs_but2', 'abs_but3'];
    
    champsVide.forEach(champ => {
        const element = document.getElementById(champ);
        if (element) element.textContent = '';
    });
    
    // Vider les compétences
    viderCompetences();
    
    // Vider les avis
    document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => {
        radio.checked = false;
    });
    
    // Vider le commentaire
    const commentaireTextarea = document.getElementById('commentaireAvis');
    if (commentaireTextarea) {
        commentaireTextarea.value = '';
    }
    
    // Remettre à zéro les statistiques d'avis
    const statsIds = [
        'stats_ecole_tres_favorable', 'stats_ecole_favorable', 'stats_ecole_assez_favorable', 
        'stats_ecole_sans_avis', 'stats_ecole_reserve',
        'stats_master_tres_favorable', 'stats_master_favorable', 'stats_master_assez_favorable', 
        'stats_master_sans_avis', 'stats_master_reserve'
    ];
    
    statsIds.forEach(id => {
        const element = document.getElementById(id);
        if (element) element.textContent = '0';
    });
}

// Gestionnaire unifié pour le changement d'année
document.getElementById('anneePromotion').addEventListener('change', function()
{
    const annee     = this.value;
    const selectEtu = document.getElementById('nomEtudiant');

    // Vider la liste des étudiants
    selectEtu.innerHTML = '<option value="">Sélectionner un étudiant</option>';
    
    // Vider toutes les données affichées
    viderToutesDonnees();
    
    // Remettre à zéro le compteur
    document.getElementById('nbAvisPromo').textContent = '0';
    
    // Vider le tableau des étudiants
    tabEtudiants = [];

    if (annee)
    {
        fetch('/api/etudiants/' + annee).then(reponse => reponse.json()).then(data =>
        {
            tabEtudiants = data;
            data.forEach(etudiant =>
            {
                const option = document.createElement('option');
                option.value = etudiant.idEtudiant;
                option.textContent = `${etudiant.nomEtudiant} ${etudiant.prenomEtudiant}`;
                selectEtu.appendChild(option);
            });

            document.getElementById('nbAvisPromo').textContent = data.length;
            
            // Actualiser les statistiques des avis seulement si une année est sélectionnée
            actualiserStatistiquesAvis();
        }).catch(error => console.error('Erreur lors du chargement des étudiants:', error));
    }
});

// Fonction pour activer le mode édition
function activerModeEdition() {
    const idEtudiant = document.getElementById('nomEtudiant').value;
    
    // Double vérification (normalement pas nécessaire maintenant)
    if (!idEtudiant) {
        return;
    }
    
    // Sauvegarder les valeurs actuelles
    valeurs_originales = {
        apprentissage_but3: document.getElementById('apprentissage_but3').textContent,
        mobilite_etranger: document.getElementById('mobilite_etranger').textContent,
        commentaire: document.getElementById('commentaireAvis').value,
        avis_ecole: document.querySelector('input[name="avis_ecole_ingenieur"]:checked')?.value || '',
        avis_master: document.querySelector('input[name="avis_master"]:checked')?.value || ''
    };
    
    // Activer seulement le champ d'édition pour apprentissage BUT 3
    document.getElementById('apprentissage_but3').classList.add('d-none');
    document.getElementById('apprentissage_but3_edit').classList.remove('d-none');
    document.getElementById('apprentissage_but3_edit').value = valeurs_originales.apprentissage_but3;
    
    // Activer le champ mobilité
    document.getElementById('mobilite_etranger').classList.add('d-none');
    document.getElementById('mobilite_etranger_edit').classList.remove('d-none');
    document.getElementById('mobilite_etranger_edit').value = valeurs_originales.mobilite_etranger;
    
    // Activer les avis et commentaire
    document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => {
        radio.disabled = false;
    });
    
    document.getElementById('commentaireAvis').disabled = false;
}

// Fonction pour annuler les modifications
function annulerModifications() {
    // Désactiver le mode édition
    desactiverModeEdition();
    
    // Restaurer les valeurs originales
    document.getElementById('apprentissage_but3').textContent = valeurs_originales.apprentissage_but3;
    document.getElementById('mobilite_etranger').textContent = valeurs_originales.mobilite_etranger;
    document.getElementById('commentaireAvis').value = valeurs_originales.commentaire;
    
    // Restaurer les avis
    document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => {
        radio.checked = false;
    });
    
    if (valeurs_originales.avis_ecole) {
        const radioEcole = document.querySelector(`input[name="avis_ecole_ingenieur"][value="${valeurs_originales.avis_ecole}"]`);
        if (radioEcole) radioEcole.checked = true;
    }
    
    if (valeurs_originales.avis_master) {
        const radioMaster = document.querySelector(`input[name="avis_master"][value="${valeurs_originales.avis_master}"]`);
        if (radioMaster) radioMaster.checked = true;
    }
}

// Fonction pour enregistrer les modifications
function enregistrerModifications() {
    const idEtudiant = document.getElementById('nomEtudiant').value;
    
    if (!idEtudiant) {
        alert('Erreur: aucun étudiant sélectionné');
        return;
    }
    
    // Récupérer les nouvelles valeurs
    const nouvelles_valeurs = {
        apprentissage_but3: document.getElementById('apprentissage_but3_edit').value,
        mobilite_etranger: document.getElementById('mobilite_etranger_edit').value,
        commentaire: document.getElementById('commentaireAvis').value,
        avis_ecole: document.querySelector('input[name="avis_ecole_ingenieur"]:checked')?.value || '',
        avis_master: document.querySelector('input[name="avis_master"]:checked')?.value || ''
    };
    
    // Sauvegarder seulement l'apprentissage BUT 3 et mobilité
    sauvegarderApprentissageEtMobilite(idEtudiant, nouvelles_valeurs);
    
    // Sauvegarder le commentaire
    if (nouvelles_valeurs.commentaire !== valeurs_originales.commentaire) {
        sauvegarderCommentaire(idEtudiant, nouvelles_valeurs.commentaire);
    }
    
    // Sauvegarder les avis
    if (nouvelles_valeurs.avis_ecole && nouvelles_valeurs.avis_ecole !== valeurs_originales.avis_ecole) {
        sauvegarderAvis(idEtudiant, 'ecole_ingenieur', nouvelles_valeurs.avis_ecole);
    }
    
    if (nouvelles_valeurs.avis_master && nouvelles_valeurs.avis_master !== valeurs_originales.avis_master) {
        sauvegarderAvis(idEtudiant, 'master', nouvelles_valeurs.avis_master);
    }
    
    // Mettre à jour l'affichage
    document.getElementById('apprentissage_but3').textContent = nouvelles_valeurs.apprentissage_but3;
    document.getElementById('mobilite_etranger').textContent = nouvelles_valeurs.mobilite_etranger;
    
    // Désactiver le mode édition
    desactiverModeEdition();
}

function desactiverModeEdition() {
    // Désactiver seulement le champ d'édition pour apprentissage BUT 3
    document.getElementById('apprentissage_but3').classList.remove('d-none');
    document.getElementById('apprentissage_but3_edit').classList.add('d-none');
    
    // Désactiver le champ mobilité
    document.getElementById('mobilite_etranger').classList.remove('d-none');
    document.getElementById('mobilite_etranger_edit').classList.add('d-none');
    
    // Désactiver les avis et commentaire
    document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => {
        radio.disabled = true;
    });
    
    document.getElementById('commentaireAvis').disabled = true;
}

function sauvegarderApprentissageEtMobilite(idEtudiant, valeurs) {
    fetch('/api/etudiant/modifier', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            idEtudiant: idEtudiant,
            apprentissage_but3: valeurs.apprentissage_but3,
            mobilite_etranger: valeurs.mobilite_etranger
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Apprentissage BUT 3 et mobilité sauvegardés');
            
            // Mettre à jour l'objet étudiant en mémoire
            const etudiant = tabEtudiants.find(e => e.idEtudiant === idEtudiant);
            if (etudiant) {
                etudiant.mobiliteEtranger = valeurs.mobilite_etranger;
            }
        } else {
            alert('Erreur lors de la sauvegarde: ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion');
    });
}

// Modifier le gestionnaire d'événements pour les avis (désactiver en mode normal)
document.addEventListener('change', function(e) {
    if (e.target.type === 'radio' && e.target.name.startsWith('avis_')) {
        // Ne pas sauvegarder immédiatement, attendre la validation
        if (!e.target.disabled) {
            console.log('Avis sélectionné (en attente de validation):', e.target.value);
        }
    }
});

// Modifier le gestionnaire pour le commentaire (désactiver la sauvegarde automatique)
document.getElementById('commentaireAvis').addEventListener('blur', function() {
    // Ne plus sauvegarder automatiquement, attendre la validation
    if (!this.disabled) {
        console.log('Commentaire modifié (en attente de validation)');
    }
});

// Au chargement d'un étudiant, désactiver les champs par défaut
document.getElementById('nomEtudiant').addEventListener('change', function()
{
    const id       = this.value;
    const option   = this.selectedOptions[0];
    const etudiant = tabEtudiants.find(e => e.idEtudiant === id);
    
    document.getElementById('ficheNomPrenom').textContent = option && id ? option.textContent : '';

    if (etudiant)
    {
        const parcours = etudiant.parcoursEtudes ?etudiant.parcoursEtudes.replace(/\s+/g, '') : '';
        const mapBut   = { 'S1S2': 'BUT 1', 'S3S4': 'BUT 2', 'S5S6': 'BUT 3' };
        
        const n2 = parcours.slice(-12, -8);
        const n1 = parcours.slice(-8, -4);
        const n  = parcours.slice(-4);

        document.getElementById('parcours_n2'       ).textContent = mapBut[n2] || n2;
        document.getElementById('parcours_n1'       ).textContent = mapBut[n1] || n1;
        document.getElementById('parcours_n'        ).textContent = mapBut[n]  || n;
        document.getElementById('parcours_but'      ).textContent = etudiant.parcoursBUT      || '';
        document.getElementById('mobilite_etranger' ).textContent = etudiant.mobiliteEtranger || '';
    }
    else
    {
        const champsEtudiant = ['parcours_n2', 'parcours_n1', 'parcours_n', 'parcours_but', 'mobilite_etranger'];
        champsEtudiant.forEach(champ => document.getElementById(champ).textContent = '');
    }

    if (id)
    {
        console.log('Chargement des données pour étudiant:', id);
        
        fetch('/api/absences/' + id)
            .then(reponse => reponse.json())
            .then(absences => {
                console.log('Absences reçues:', absences);
                document.getElementById('abs_but1').textContent = absences.but1;
                document.getElementById('abs_but2').textContent = absences.but2;
                document.getElementById('abs_but3').textContent = absences.but3;
            })
            .catch(error => console.error('Erreur absences:', error));

        fetch('/api/apprentissage/' + id)
            .then(reponse => reponse.json())
            .then(apprentissage => {
                console.log('Apprentissage reçu:', apprentissage);
                document.getElementById('apprentissage_but1').textContent = apprentissage.but1;
                document.getElementById('apprentissage_but2').textContent = apprentissage.but2;
                document.getElementById('apprentissage_but3').textContent = apprentissage.but3;
            })
            .catch(error => console.error('Erreur apprentissage:', error));

        fetch('/api/competences/' + id)
            .then(reponse => reponse.json())
            .then(competences => {
                console.log('Compétences reçues:', competences);
                viderCompetences();
                afficherCompetences(competences);
            })
            .catch(error => console.error('Erreur compétences:', error));

        fetch('/api/ressources/' + id)
            .then(reponse => reponse.json())
            .then(ressources => {
                console.log('Ressources reçues:', ressources);
                afficherRessources(ressources);
            })
            .catch(error => console.error('Erreur ressources:', error));

        // Charger les avis de l'étudiant
        fetch('/api/avis/etudiant/' + id)
            .then(reponse => reponse.json())
            .then(avis => {
                console.log('Avis reçus:', avis);
                
                // Vider tous les radio buttons d'abord
                document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => {
                    radio.checked = false;
                });
                
                // Cocher les avis appropriés
                if (avis.ecole_ingenieur) {
                    const radioEcole = document.querySelector(`input[name="avis_ecole_ingenieur"][value="${avis.ecole_ingenieur}"]`);
                    if (radioEcole) radioEcole.checked = true;
                }
                
                if (avis.master) {
                    const radioMaster = document.querySelector(`input[name="avis_master"][value="${avis.master}"]`);
                    if (radioMaster) radioMaster.checked = true;
                }
                
                // Remplir le commentaire
                const commentaireTextarea = document.getElementById('commentaireAvis');
                if (commentaireTextarea) {
                    commentaireTextarea.value = avis.commentaire || '';
                }
            })
            .catch(error => console.error('Erreur avis:', error));
    }
    else
    {
        ['abs_but1', 'abs_but2', 'abs_but3', 'apprentissage_but1', 'apprentissage_but2', 'apprentissage_but3'].forEach
        (
            champ => document.getElementById(champ).textContent = ''
        );

        viderCompetences();
        
        // Vider les avis si aucun étudiant sélectionné
        document.querySelectorAll('input[type="radio"][name^="avis_"]').forEach(radio => {
            radio.checked = false;
        });
        
        const commentaireTextarea = document.getElementById('commentaireAvis');
        if (commentaireTextarea) {
            commentaireTextarea.value = '';
        }
    }
});

function remplirAnneesImport() {
    const selectAnneeImport = document.getElementById('anneeImport');
    
    if (!selectAnneeImport) return;
    
    const anneeActuelle = new Date().getFullYear();
    
    // Vider le select et garder l'option par défaut
    selectAnneeImport.innerHTML = '<option value="" selected>Année de la promotion</option>';
    
    // Ajouter les 4 dernières années (actuelle + 3 précédentes)
    for (let i = 0; i < 4; i++) {
        const annee = anneeActuelle - i;
        const option = document.createElement('option');
        option.value = annee;
        option.textContent = annee;
        selectAnneeImport.appendChild(option);
    }
};

// Ajouter une validation avant l'import
document.getElementById('importForm').addEventListener('submit', function(e) {
    const fichiers = this.querySelector('input[type="file"]').files;
    
    if (fichiers.length === 0) {
        e.preventDefault();
        alert('Veuillez sélectionner au moins un fichier');
        return;
    }
    
    // Vous pouvez ajouter ici une validation des noms de fichiers
    // pour vérifier qu'ils ne contiennent pas de semestres > 5
    for (let i = 0; i < fichiers.length; i++) {
        const nomFichier = fichiers[i].name.toLowerCase();
        
        // Vérifier si le nom contient 's6' ou 'semestre6' etc.
        if (nomFichier.includes('s6') || nomFichier.includes('semestre6') || nomFichier.includes('sem6')) {
            e.preventDefault();
            alert('Les fichiers contenant des données du semestre 6 ne sont pas autorisés');
            return;
        }
    }
});

function exporterPDF() {
    const idEtudiant = document.getElementById('nomEtudiant').value; // CORRIGÉ : utiliser nomEtudiant au lieu de idEtudiant
    const anneePromotion = document.getElementById('anneePromotion').value;
    
    if (!idEtudiant) {
        alert('Veuillez sélectionner un étudiant');
        return;
    }

    // Récupérer toutes les données affichées
    const donneesPDF = {
        nomEtudiant: document.getElementById('ficheNomPrenom').textContent, // CORRIGÉ : utiliser ficheNomPrenom
        anneePromotion: anneePromotion,
        parcoursBUT: document.getElementById('parcours_but').textContent, // CORRIGÉ : utiliser parcours_but
        mobiliteEtranger: document.getElementById('mobilite_etranger').textContent || '', // CORRIGÉ : utiliser mobilite_etranger
        
        // Apprentissage - CORRIGÉ : utiliser les bons IDs et textContent
        apprentissageBUT1: document.getElementById('apprentissage_but1').textContent,
        apprentissageBUT2: document.getElementById('apprentissage_but2').textContent,
        apprentissageBUT3: document.getElementById('apprentissage_but3').textContent,
        
        // Parcours d'études
        parcoursN2: document.getElementById('parcours_n2').textContent,
        parcoursN1: document.getElementById('parcours_n1').textContent,
        parcoursN: document.getElementById('parcours_n').textContent,
        
        // Absences - CORRIGÉ : utiliser les bons IDs
        absences: {
            but1: document.getElementById('abs_but1').textContent || '',
            but2: document.getElementById('abs_but2').textContent || '',
            but3: document.getElementById('abs_but3').textContent || ''
        },
        
        // Compétences
        competences: {},
        
        // Ressources
        ressources: {},
        
        // Avis - CORRIGÉ : utiliser le bon ID pour le commentaire
        avis: {
            ecole_ingenieur: document.querySelector('input[name="avis_ecole_ingenieur"]:checked')?.value || '',
            master: document.querySelector('input[name="avis_master"]:checked')?.value || '',
            commentaire: document.getElementById('commentaireAvis').value || '' // CORRIGÉ : commentaireAvis au lieu de commentaire
        },
        
        // Statistiques
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
    
    // Récupérer les compétences
    const competenceIds = [
        'BIN1_but1_moy', 'BIN1_but1_rang', 'BIN1_but2_moy', 'BIN1_but2_rang', 'BIN1_but3_moy', 'BIN1_but3_rang',
        'BIN2_but1_moy', 'BIN2_but1_rang', 'BIN2_but2_moy', 'BIN2_but2_rang', 'BIN2_but3_moy', 'BIN2_but3_rang',
        'BIN3_but1_moy', 'BIN3_but1_rang', 'BIN3_but2_moy', 'BIN3_but2_rang',
        'BIN4_but1_moy'   , 'BIN4_but1_rang'   , 'BIN4_but2_moy'   , 'BIN4_but2_rang' ,
        'BIN5_but1_moy'   , 'BIN5_but1_rang'   , 'BIN5_but2_moy'   , 'BIN5_but2_rang' ,
        'BIN6_but1_moy'   , 'BIN6_but1_rang'   , 'BIN6_but2_moy'   , 'BIN6_but2_rang' , 'BIN6_but3_moy', 'BIN6_but3_rang'
    ];
    
    competenceIds.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            donneesPDF.competences[id] = element.textContent || '';
        }
    });
    
    // Récupérer les ressources (maths et anglais)
    const ressourceIds = [
        'maths_but1_moy', 'maths_but1_rang', 'maths_but2_moy', 'maths_but2_rang', 'maths_but3_moy', 'maths_but3_rang',
        'anglais_but1_moy', 'anglais_but1_rang', 'anglais_but2_moy', 'anglais_but2_rang'
    ];
    
    ressourceIds.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            donneesPDF.ressources[id] = element.textContent || '';
        }
    });

    console.log('Données PDF envoyées:', donneesPDF); // Pour debug
    
    // Envoyer les données au contrôleur
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
    
    // Fermer le menu flottant
    document.getElementById('floatingMenu').style.display = 'none';
}