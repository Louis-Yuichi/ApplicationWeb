function toggleFiltre(idFiltre) {
    window.location.href = '/toggleFiltre/' + idFiltre;
}

function supprimerFiltre(idFiltre) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce filtre ?')) {
        window.location.href = '/supprimerFiltre/' + idFiltre;
    }
}

function toggleValeurCondition(select) {
    const valeurDiv = document.getElementById('valeurConditionDiv');
    const valeurInput = document.getElementById('valeurConditionInput');
    
    if (select.value === 'vide' || select.value === 'non_vide') {
        valeurDiv.style.display = 'none';
        valeurInput.required = false;
        valeurInput.value = '';
    } else {
        valeurDiv.style.display = 'block';
        valeurInput.required = true;
    }
}
