const toggleButton = document.getElementById('toggle-btn')
const sidebar = document.getElementById('sidebar')

function toggleSidebar() {
    sidebar.classList.toggle('close')
    toggleButton.classList.toggle('rotate')
}

const clientElements = document.querySelectorAll('#client-data div');
const clients = Array.from(clientElements).map(el => ({
    id: parseInt(el.dataset.id),
    name: el.dataset.name
}));

document.getElementById('search-button').addEventListener('click', function () {
    const input = document.getElementById('search-input').value.toLowerCase();
    const results = clients.filter(client =>
        client.id.toString().includes(input) ||
        client.name.toLowerCase().includes(input)
    );
    displayResults(results);
});

document.getElementById('search-input').addEventListener('keydown', function (event){
    if (event.key === 'Enter') {
        document.getElementById('search-button').click();
    }
});

document.getElementById('search-button').addEventListener('click', function () {
    const input = document.getElementById('search-input').value.toLowerCase().trim();

    if (input === '') {
        document.getElementById('results').innerHTML = ''; // Clear previous results
        return; // Stop the function
    }

    const results = clients.filter(client =>
        client.id.toString().includes(input) ||
        client.name.toLowerCase().includes(input)
    );
    displayResults(results);
});

document.getElementById('show-all').addEventListener('click', function () {
    const input = document.getElementById('search-input').value.toLowerCase();
    const results = clients.filter(client =>
        client.id.toString().includes(input) ||
        client.name.toLowerCase().includes(input)
    );
    displayResults(results);
});

function displayResults(results) {
    const resultsContainer = document.getElementById('results');
    resultsContainer.innerHTML = ''; // Clear previous results

    if (results.length === 0) {
        resultsContainer.innerHTML = '<p>No results found.</p>';
        return;
    }

    results.forEach(client => {
        const div = document.createElement('div');
        div.textContent = `ID: ${client.id}, Name: ${client.name}`;
        resultsContainer.appendChild(div);
    });
}