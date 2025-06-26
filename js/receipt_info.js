function enableEdit(rowId) {
    const row = document.getElementById('row-' + rowId);
    if (!row) return;

    const supplierCell = row.querySelector('.cell-supplier');
    const dateCell = row.querySelector('.cell-date');
    const totalCell = row.querySelector('.cell-total');
    const actionsCell = row.querySelector('.cell-actions');

    // Backup original text for cancel
    row.dataset.originalSupplier = supplierCell.textContent;
    row.dataset.originalDate = dateCell.textContent;
    row.dataset.originalTotal = totalCell.textContent;

    // Replace cells with inputs prefilled
    supplierCell.innerHTML = `<input type="text" name="edit_supplier" value="${supplierCell.textContent.trim()}" required form="form-${rowId}"/>`;
    dateCell.innerHTML = `<input type="date" name="edit_receipt_date" value="${dateCell.textContent.trim()}" required form="form-${rowId}"/>`;

    let totalValue = totalCell.textContent.trim().replace(/,/g, '');
    totalCell.innerHTML = `<input type="number" name="edit_total" step="0.01" value="${totalValue}" required form="form-${rowId}"/>`;

    // Change actions: Edit/Delete -> Save/Cancel inside form with unique id
    actionsCell.innerHTML = `
        <form method="POST" id="form-${rowId}" class="inline-form" onsubmit="return validateEdit(this);">
            <input type="hidden" name="edit_id" value="${rowId}">
            <input type="hidden" name="action" value="edit">
            <button type="submit" class="save">Save</button>
            <button type="button" class="cancel" onclick="cancelEdit('${rowId}')">Cancel</button>
        </form>
    `;
}

function cancelEdit(rowId) {
    const row = document.getElementById('row-' + rowId);
    if (!row) return;
    const supplierCell = row.querySelector('.cell-supplier');
    const dateCell = row.querySelector('.cell-date');
    const totalCell = row.querySelector('.cell-total');
    const actionsCell = row.querySelector('.cell-actions');

    // Restore original text
    supplierCell.textContent = row.dataset.originalSupplier;
    dateCell.textContent = row.dataset.originalDate;
    totalCell.textContent = parseFloat(row.dataset.originalTotal).toFixed(2);

    // Restore actions cell
    actionsCell.innerHTML = `
        <button class="edit" type="button" onclick="enableEdit('${rowId}')">Edit</button>
        <form method="POST" class="inline-form" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this receipt?');">
            <input type="hidden" name="delete_id" value="${rowId}">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="delete">Delete</button>
        </form>
    `;
}

function validateEdit(form) {
    let supplier = form.querySelector('input[name="edit_supplier"]').value.trim();
    let date = form.querySelector('input[name="edit_receipt_date"]').value;
    let total = form.querySelector('input[name="edit_total"]').value.trim();

    if (!supplier) {
        alert('Supplier is required');
        return false;
    }
    if (!date) {
        alert('Date is required');
        return false;
    }
    if (!total || isNaN(total) || Number(total) <= 0) {
        alert('Please enter a valid total amount > 0');
        return false;
    }
    return true; // allow submission
}

// --- Updated toggleSummaryOptions ---
function toggleSummaryOptions() {
    const type = document.getElementById("summary-type").value;

    // Hide all option sections
    document.getElementById("annual-options").style.display = "none";
    document.getElementById("quarterly-options").style.display = "none";
    document.getElementById("monthly-options").style.display = "none";
    document.getElementById("custom-options").style.display = "none";

    // Remove all names
    document.getElementById("annual_year").removeAttribute("name");
    document.getElementById("quarterly_year").removeAttribute("name");
    document.getElementById("quarter").removeAttribute("name");
    document.getElementById("monthly_year").removeAttribute("name");
    document.getElementById("month").removeAttribute("name");
    document.getElementById("from_date").removeAttribute("name");
    document.getElementById("to_date").removeAttribute("name");

    // Set correct names and show relevant section
    if (type === "annual") {
        document.getElementById("annual-options").style.display = "block";
        document.getElementById("annual_year").setAttribute("name", "year");
    } else if (type === "quarterly") {
        document.getElementById("quarterly-options").style.display = "block";
        document.getElementById("quarterly_year").setAttribute("name", "year");
        document.getElementById("quarter").setAttribute("name", "quarter");
    } else if (type === "monthly") {
        document.getElementById("monthly-options").style.display = "block";
        document.getElementById("monthly_year").setAttribute("name", "year");
        document.getElementById("month").setAttribute("name", "month");
    } else if (type === "custom") {
        document.getElementById("custom-options").style.display = "block";
        document.getElementById("from_date").setAttribute("name", "from_date");
        document.getElementById("to_date").setAttribute("name", "to_date");
    }
}

document.addEventListener("DOMContentLoaded", toggleSummaryOptions);

function validateDateRange(fromDate, toDate) {
    if (new Date(fromDate) > new Date(toDate)) {
        alert('The "From" date must be before the "To" date.');
        return false;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.tab-link');
    const contents = document.querySelectorAll('.tab-content');
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('active_tab') || 'input-details';

    function showTab(tabId) {
        tabs.forEach(tab => {
            tab.classList.remove('active');
            tab.setAttribute('aria-selected', 'false');
        });
        contents.forEach(content => content.classList.remove('active'));
        const targetTab = document.querySelector(`.tab-link[data-tab="${tabId}"]`);
        if (targetTab) {
            targetTab.classList.add('active');
            targetTab.setAttribute('aria-selected', 'true');
            document.getElementById(tabId).classList.add('active');
        }
    }

    showTab(activeTab);

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabId = tab.getAttribute('data-tab');
            showTab(tabId);
        });
    });
});
