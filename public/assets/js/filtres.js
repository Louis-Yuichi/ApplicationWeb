function toggleFiltre(idFiltre) {
    window.location.href = '/toggleFiltre/' + idFiltre;
}

function supprimerFiltre(idFiltre) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce filtre ?')) {
        window.location.href = '/supprimerFiltre/' + idFiltre;
    }
}