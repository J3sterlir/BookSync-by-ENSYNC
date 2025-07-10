// Track current client
let currentClientId = null;

// Set the current client ID and update hidden input fields
function setCurrentClient(clientId) {
    console.log("Setting current client ID:", clientId); // Debugging line
    currentClientId = clientId;
    document.getElementById('modal_client_id').value = clientId;
    document.getElementById('summary_client_id').value = clientId;
    document.getElementById('history_client_id').value = clientId;
}

// Function to open the specified modal
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

// Function to close the specified modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside the modal content
window.onclick = function(event) {
    const modals = ['inputModal', 'summaryModal', 'historyModal'];
    modals.forEach(modalId => {
        if (event.target.id === modalId) {
            closeModal(modalId);
        }
    });
}

// Summary type selector logic
document.getElementById('summary_type').addEventListener('change', function() {
    const val = this.value;
    document.getElementById('annualOptions').classList.add('hidden');
    document.getElementById('quarterlyOptions').classList.add('hidden');
    document.getElementById('customOptions').classList.add('hidden');
    
    if (val === 'annual') {
        document.getElementById('annualOptions').classList.remove('hidden');
    } else if (val === 'quarterly') {
        document.getElementById('quarterlyOptions').classList.remove('hidden');
    } else if (val === 'custom') {
        document.getElementById('customOptions').classList.remove('hidden');
    }
});

// Trigger change event on load to set initial visibility
document.getElementById('summary_type').dispatchEvent(new Event('change'));

// Add event listeners for Edit and Delete buttons in the receipt history
document.addEventListener('click', function(event) {
    if (event.target.matches('.bg-yellow-100')) {
        const receiptId = event.target.closest('.receipt-item').querySelector('h3').innerText.split('#')[1];
        console.log("Edit receipt ID:", receiptId); // Debugging line
        // Add logic to open edit modal and populate fields
    } else if (event.target.matches('.bg-red-100')) {
        const receiptId = event.target.closest('.receipt-item').querySelector('h3').innerText.split('#')[1];
        console.log("Delete receipt ID:", receiptId); // Debugging line
        // Add logic to confirm deletion and send request to delete
    }
});
