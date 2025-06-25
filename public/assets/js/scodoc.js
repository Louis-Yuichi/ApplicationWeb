document.addEventListener('DOMContentLoaded', function()
{
	const btnModifier = document.getElementById('btnModifier');
	const btnGroup    = document.getElementById('btnGroup'   );
	const btnAnnuler  = document.getElementById('btnAnnuler' );
	const btnValider  = document.getElementById('btnValider' );

	btnModifier.addEventListener('click', function()
	{
		btnModifier.classList.add('d-none');
		btnGroup.classList.remove('d-none');
	});

	btnAnnuler.addEventListener('click', function()
	{
		btnGroup.classList.add('d-none');
		btnModifier.classList.remove('d-none');
	});

	btnValider.addEventListener('click', function()
	{
		btnGroup.classList.add('d-none');
		btnModifier.classList.remove('d-none');
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
});

let tabEtudiants = [];

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
        const parcours = etudiant.parcoursEtudes ? etudiant.parcoursEtudes.replace(/\s+/g, '') : '';
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
    }
    else
    {
        ['abs_but1', 'abs_but2', 'abs_but3', 'apprentissage_but1', 'apprentissage_but2', 'apprentissage_but3'].forEach
        (
            champ => document.getElementById(champ).textContent = ''
        );

        viderCompetences();
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
		'anglais_but1_moy', 'anglais_but1_rang', 'anglais_but2_moy', 'anglais_but2_rang'
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

        // Mapping des codes ressources vers les matières
        let matiere;
        if (['BINR106', 'BINR107', 'BINR207', 'BINR208', 'BINR209', 'BINR308', 'BINR309', 'BINR403', 'BINR404', 'BINR504', 'BINR511', 'BINR512'].includes(codeRes)) {
            matiere = 'maths';
        } else if (['BINR110', 'BINR212', 'BINR312', 'BINR412'].includes(codeRes)) {
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