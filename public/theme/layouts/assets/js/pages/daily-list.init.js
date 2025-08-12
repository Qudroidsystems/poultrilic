document.addEventListener('DOMContentLoaded', function () {
    console.log('daily-list.init.js: Loaded');

    // CSRF Token Setup
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) {
        console.error('CSRF token not found');
        alert('CSRF token missing. Please refresh the page.');
        return;
    }

    const CRATE_SIZE = 30;

    // Helper function to format total pieces to crates and pieces
    function toCratesAndPieces(totalPieces) {
        const crates = Math.floor(totalPieces / CRATE_SIZE);
        const pieces = totalPieces % CRATE_SIZE;
        return { crates, pieces, total: totalPieces };
    }

    // Display error message
    function displayError(elementId, message) {
        const errorElement = document.getElementById(elementId);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('d-none');
        }
    }

    // Clear error message
    function clearError(elementId) {
        const errorElement = document.getElementById(elementId);
        if (errorElement) {
            errorElement.classList.add('d-none');
        }
    }

    // Update outstanding egg display
    function updateOutstandingEgg(modalPrefix) {
        const productionCrates = parseInt(document.getElementById(`${modalPrefix}_daily_egg_production_crates`)?.value) || 0;
        const productionPieces = parseInt(document.getElementById(`${modalPrefix}_daily_egg_production_pieces`)?.value) || 0;
        const soldCrates = parseInt(document.getElementById(`${modalPrefix}_daily_sold_egg_crates`)?.value) || 0;
        const soldPieces = parseInt(document.getElementById(`${modalPrefix}_daily_sold_egg_pieces`)?.value) || 0;
        const brokenCrates = parseInt(document.getElementById(`${modalPrefix}_broken_egg_crates`)?.value) || 0;
        const brokenPieces = parseInt(document.getElementById(`${modalPrefix}_broken_egg_pieces`)?.value) || 0;

        const production = productionCrates * CRATE_SIZE + productionPieces;
        const sold = soldCrates * CRATE_SIZE + soldPieces;
        const broken = brokenCrates * CRATE_SIZE + brokenPieces;
        const outstanding = production - sold - broken;

        const outstandingDisplay = document.getElementById(`${modalPrefix}_outstanding_egg`);
        if (outstandingDisplay) {
            outstandingDisplay.textContent = `${toCratesAndPieces(outstanding).crates} cr ${toCratesAndPieces(outstanding).pieces} pcs (${outstanding} pieces)`;
        }
    }

    // Filter Data
    window.filterData = function (url = null) {
        console.log('filterData:', { url });
        const search = document.getElementById('searchDay')?.value || '';
        const dayFilter = document.getElementById('dayFilter')?.value || 'all';
        const fetchUrl = url || `/daily-entries/${window.weekId}?search=${encodeURIComponent(search)}&day_filter=${encodeURIComponent(dayFilter)}`;
        fetch(fetchUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw new Error(JSON.stringify({ status: response.status, ...err })); });
            }
            return response.json();
        })
        .then(data => {
            const tbody = document.querySelector('#dailyTable tbody');
            if (!tbody) {
                console.error('Table body not found');
                return;
            }
            tbody.innerHTML = data.dailyEntries.map(entry => `
                <tr>
                    <td><input type="checkbox" class="chk-child" value="${entry.id}" data-id="${entry.id}"></td>
                    <td class="day_number">${entry.day_number}</td>
                    <td class="daily_feeds">${entry.daily_feeds}</td>
                    <td class="daily_mortality">${entry.daily_mortality}</td>
                    <td class="current_birds">${entry.current_birds}</td>
                    <td class="daily_egg_production">${toCratesAndPieces(entry.daily_egg_production).crates} cr ${toCratesAndPieces(entry.daily_egg_production).pieces} pcs (${entry.daily_egg_production} pieces)</td>
                    <td class="daily_sold_egg">${toCratesAndPieces(entry.daily_sold_egg).crates} cr ${toCratesAndPieces(entry.daily_sold_egg).pieces} pcs (${entry.daily_sold_egg} pieces)</td>
                    <td class="broken_egg">${toCratesAndPieces(entry.broken_egg).crates} cr ${toCratesAndPieces(entry.broken_egg).pieces} pcs (${entry.broken_egg} pieces)</td>
                    <td class="outstanding_egg">${toCratesAndPieces(entry.outstanding_egg).crates} cr ${toCratesAndPieces(entry.outstanding_egg).pieces} pcs (${entry.outstanding_egg} pieces)</td>
                    <td class="total_egg_in_farm">${toCratesAndPieces(entry.total_egg_in_farm).crates} cr ${toCratesAndPieces(entry.total_egg_in_farm).pieces} pcs (${entry.total_egg_in_farm} pieces)</td>
                    <td class="created_at">${entry.created_at}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-subtle-secondary edit-item-btn" data-id="${entry.id}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-subtle-danger remove-item-btn" data-id="${entry.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            const paginationElement = document.getElementById('pagination-element');
            if (paginationElement) {
                paginationElement.innerHTML = data.pagination;
            }
            if (window.dailyChart) {
                window.dailyChart.data.labels = data.chartData.labels;
                window.dailyChart.data.datasets[0].data = data.chartData.daily_egg_production;
                window.dailyChart.update();
            }
            console.log('filterData: Updated');
            bindPagination();
        })
        .catch(error => {
            console.error('filterData error:', error);
            let errorMsg = 'Failed to load data';
            try {
                const err = JSON.parse(error.message);
                errorMsg = err.status === 403 ? 'You are not authorized to view this data' : 
                           err.errors ? Object.values(err.errors).flat().join(', ') : err.message;
            } catch (e) {
                errorMsg = 'An unexpected error occurred. Please try again.';
            }
            alert(errorMsg);
        });
    };

    // Bind Pagination
    function bindPagination() {
        console.log('bindPagination');
        document.querySelectorAll('#pagination-element .page-link').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const href = link.getAttribute('href');
                if (href && href !== '#') {
                    console.log('Pagination:', href);
                    window.filterData(href);
                }
            });
        });
    }
    bindPagination();

    // Add Entry
    const addForm = document.getElementById('add-daily-form');
    if (addForm) {
        // Add input event listeners for dynamic outstanding egg calculation
        ['daily_egg_production_crates', 'daily_egg_production_pieces', 'daily_sold_egg_crates', 
         'daily_sold_egg_pieces', 'broken_egg_crates', 'broken_egg_pieces'].forEach(field => {
            const element = document.getElementById(`add_${field}`);
            if (element) {
                element.addEventListener('input', () => updateOutstandingEgg('add'));
            }
        });

        addForm.addEventListener('submit', function (e) {
            e.preventDefault();
            console.log('Add modal: Submit');
            clearError('add-error-msg');
            const formData = new FormData(this);

            const dailyEggProductionCrates = parseInt(formData.get('daily_egg_production_crates')) || 0;
            const dailyEggProductionPieces = parseInt(formData.get('daily_egg_production_pieces')) || 0;
            const dailySoldEggCrates = parseInt(formData.get('daily_sold_egg_crates')) || 0;
            const dailySoldEggPieces = parseInt(formData.get('daily_sold_egg_pieces')) || 0;
            const brokenEggCrates = parseInt(formData.get('broken_egg_crates')) || 0;
            const brokenEggPieces = parseInt(formData.get('broken_egg_pieces')) || 0;

            if (isNaN(dailyEggProductionCrates) || isNaN(dailyEggProductionPieces) ||
                isNaN(dailySoldEggCrates) || isNaN(dailySoldEggPieces) ||
                isNaN(brokenEggCrates) || isNaN(brokenEggPieces)) {
                displayError('add-error-msg', 'Invalid input for egg fields. Please enter valid numbers.');
                return;
            }

            if (dailyEggProductionPieces > 29 || dailySoldEggPieces > 29 || brokenEggPieces > 29) {
                displayError('add-error-msg', 'Pieces must be between 0 and 29');
                return;
            }

            const dailyEggProduction = dailyEggProductionCrates * CRATE_SIZE + dailyEggProductionPieces;
            const dailySoldEgg = dailySoldEggCrates * CRATE_SIZE + dailySoldEggPieces;
            const brokenEgg = brokenEggCrates * CRATE_SIZE + brokenEggPieces;

            if (dailySoldEgg + brokenEgg > dailyEggProduction) {
                displayError('add-error-msg', 'The sum of sold and broken eggs cannot exceed daily egg production.');
                return;
            }

            const payload = {
                _token: formData.get('_token'),
                day_number: parseInt(formData.get('day_number')) || 0,
                daily_feeds: parseFloat(formData.get('daily_feeds')) || 0,
                available_feeds: parseFloat(formData.get('available_feeds')) || 0,
                daily_mortality: parseInt(formData.get('daily_mortality')) || 0,
                sick_bay: parseInt(formData.get('sick_bay')) || 0,
                daily_egg_production: dailyEggProduction,
                daily_sold_egg: dailySoldEgg,
                broken_egg: brokenEgg,
                drugs: formData.get('drugs') || '',
                reorder_feeds: parseFloat(formData.get('reorder_feeds')) || 0
            };

            console.log('Add Payload:', payload);

            fetch(`/daily-entries/${window.weekId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(JSON.stringify({ status: response.status, ...err })); });
                }
                return response.json();
            })
            .then(data => {
                console.log('Add modal: Success', data);
                bootstrap.Modal.getInstance(document.getElementById('addDailyModal')).hide();
                window.filterData();
                alert('Daily entry added successfully');
                addForm.reset();
                clearError('add-error-msg');
                document.getElementById('add_outstanding_egg').textContent = '0 cr 0 pcs (0 pieces)';
            })
            .catch(error => {
                console.error('Add error:', error);
                let errorMsg = 'Failed to add entry';
                try {
                    const err = JSON.parse(error.message);
                    errorMsg = err.status === 403 ? 'You are not authorized to create entries' :
                               err.status === 422 ? Object.values(err.errors).flat().join(', ') : err.message;
                } catch (e) {
                    errorMsg = 'An unexpected error occurred. Please try again.';
                }
                displayError('add-error-msg', errorMsg);
            });
        });
    } else {
        console.warn('Add form not found');
    }

    // Edit Modal
    document.addEventListener('click', function (e) {
        if (e.target.closest('.edit-item-btn')) {
            console.log('Edit modal: Opening');
            const row = e.target.closest('tr');
            const entryId = row.querySelector('.chk-child').value;
            clearError('edit-error-msg');
            fetch(`/daily-entries/${window.weekId}/${entryId}`, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(JSON.stringify({ status: response.status, ...err })); });
                }
                return response.json();
            })
            .then(data => {
                console.log('Edit modal: Data fetched', data);
                const editForm = document.getElementById('edit-daily-form');
                if (!editForm) {
                    console.error('Edit form not found');
                    return;
                }
                document.getElementById('edit-id-field').value = data.id || '';
                document.getElementById('edit_day_number').value = data.day_number.replace('Day ', '') || '';
                document.getElementById('edit_daily_feeds').value = data.daily_feeds || 0;
                document.getElementById('edit_available_feeds').value = data.available_feeds || 0;
                document.getElementById('edit_daily_mortality').value = data.daily_mortality || 0;
                document.getElementById('edit_sick_bay').value = data.sick_bay || 0;
                const dailyEggProduction = toCratesAndPieces(data.daily_egg_production || 0);
                document.getElementById('edit_daily_egg_production_crates').value = dailyEggProduction.crates;
                document.getElementById('edit_daily_egg_production_pieces').value = dailyEggProduction.pieces;
                const dailySoldEgg = toCratesAndPieces(data.daily_sold_egg || 0);
                document.getElementById('edit_daily_sold_egg_crates').value = dailySoldEgg.crates;
                document.getElementById('edit_daily_sold_egg_pieces').value = dailySoldEgg.pieces;
                const brokenEgg = toCratesAndPieces(data.broken_egg || 0);
                document.getElementById('edit_broken_egg_crates').value = brokenEgg.crates;
                document.getElementById('edit_broken_egg_pieces').value = brokenEgg.pieces;
                document.getElementById('edit_drugs').value = data.drugs || '';
                document.getElementById('edit_reorder_feeds').value = data.reorder_feeds || '';
                document.getElementById('edit_outstanding_egg').textContent = `${toCratesAndPieces(data.outstanding_egg || 0).crates} cr ${toCratesAndPieces(data.outstanding_egg || 0).pieces} pcs (${data.outstanding_egg || 0} pieces)`;
                document.getElementById('edit_total_egg_in_farm').textContent = `${toCratesAndPieces(data.total_egg_in_farm || 0).crates} cr ${toCratesAndPieces(data.total_egg_in_farm || 0).pieces} pcs (${data.total_egg_in_farm || 0} pieces)`;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('editDailyModal')).show();

                // Add input event listeners for dynamic outstanding egg calculation
                ['daily_egg_production_crates', 'daily_egg_production_pieces', 'daily_sold_egg_crates', 
                 'daily_sold_egg_pieces', 'broken_egg_crates', 'broken_egg_pieces'].forEach(field => {
                    const element = document.getElementById(`edit_${field}`);
                    if (element) {
                        element.addEventListener('input', () => updateOutstandingEgg('edit'));
                    }
                });
            })
            .catch(error => {
                console.error('Edit fetch error:', error);
                let errorMsg = 'Failed to load entry data';
                try {
                    const err = JSON.parse(error.message);
                    errorMsg = err.status === 403 ? 'You are not authorized to view this entry' :
                               err.status === 404 ? 'Entry not found' : err.message;
                } catch (e) {
                    errorMsg = 'An unexpected error occurred. Please try again.';
                }
                alert(errorMsg);
            });
        }
    });

    // Update Entry
    const editForm = document.getElementById('edit-daily-form');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            console.log('Edit modal: Submit event triggered');
            const entryId = document.getElementById('edit-id-field').value;
            clearError('edit-error-msg');
            const formData = new FormData(this);

            const dailyEggProductionCrates = parseInt(formData.get('daily_egg_production_crates')) || 0;
            const dailyEggProductionPieces = parseInt(formData.get('daily_egg_production_pieces')) || 0;
            const dailySoldEggCrates = parseInt(formData.get('daily_sold_egg_crates')) || 0;
            const dailySoldEggPieces = parseInt(formData.get('daily_sold_egg_pieces')) || 0;
            const brokenEggCrates = parseInt(formData.get('broken_egg_crates')) || 0;
            const brokenEggPieces = parseInt(formData.get('broken_egg_pieces')) || 0;

            if (isNaN(dailyEggProductionCrates) || isNaN(dailyEggProductionPieces) ||
                isNaN(dailySoldEggCrates) || isNaN(dailySoldEggPieces) ||
                isNaN(brokenEggCrates) || isNaN(brokenEggPieces)) {
                displayError('edit-error-msg', 'Invalid input for egg fields. Please enter valid numbers.');
                return;
            }

            if (dailyEggProductionPieces > 29 || dailySoldEggPieces > 29 || brokenEggPieces > 29) {
                displayError('edit-error-msg', 'Pieces must be between 0 and 29');
                return;
            }

            const dailyEggProduction = dailyEggProductionCrates * CRATE_SIZE + dailyEggProductionPieces;
            const dailySoldEgg = dailySoldEggCrates * CRATE_SIZE + dailySoldEggPieces;
            const brokenEgg = brokenEggCrates * CRATE_SIZE + brokenEggPieces;

            if (dailySoldEgg + brokenEgg > dailyEggProduction) {
                displayError('edit-error-msg', 'The sum of sold and broken eggs cannot exceed daily egg production.');
                return;
            }

            const payload = {
                _token: formData.get('_token'),
                _method: 'PUT',
                id: parseInt(formData.get('id')),
                day_number: parseInt(formData.get('day_number')) || 0,
                daily_feeds: parseFloat(formData.get('daily_feeds')) || 0,
                available_feeds: parseFloat(formData.get('available_feeds')) || 0,
                daily_mortality: parseInt(formData.get('daily_mortality')) || 0,
                sick_bay: parseInt(formData.get('sick_bay')) || 0,
                daily_egg_production: dailyEggProduction,
                daily_sold_egg: dailySoldEgg,
                broken_egg: brokenEgg,
                drugs: formData.get('drugs') || '',
                reorder_feeds: parseFloat(formData.get('reorder_feeds')) || 0
            };

            console.log('Edit Payload:', payload);

            fetch(`/daily-entries/${window.weekId}/${entryId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(JSON.stringify({ status: response.status, ...err })); });
                }
                return response.json();
            })
            .then(data => {
                console.log('Edit modal: Success', data);
                bootstrap.Modal.getInstance(document.getElementById('editDailyModal')).hide();
                window.filterData();
                alert('Daily entry updated successfully');
                clearError('edit-error-msg');
            })
            .catch(error => {
                console.error('Edit error:', error);
                let errorMsg = 'Failed to update entry';
                try {
                    const err = JSON.parse(error.message);
                    errorMsg = err.status === 403 ? 'You are not authorized to update entries' :
                               err.status === 422 ? Object.values(err.errors).flat().join(', ') :
                               err.status === 404 ? 'Entry not found' : err.message;
                } catch (e) {
                    errorMsg = 'An unexpected error occurred. Please try again.';
                }
                displayError('edit-error-msg', errorMsg);
            });
        });
    } else {
        console.warn('Edit form not found');
    }

    // Delete Modal
    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-item-btn')) {
            console.log('Delete modal: Opening');
            const row = e.target.closest('tr');
            const entryId = row.querySelector('.chk-child').value;
            const confirmDelete = confirm('Are you sure you want to delete this daily entry?');
            if (confirmDelete) {
                fetch(`/daily-entries/${window.weekId}/${entryId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(JSON.stringify({ status: response.status, ...err })); });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Delete modal: Success', data);
                    window.filterData();
                    alert('Daily entry deleted successfully');
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    let errorMsg = 'Failed to delete entry';
                    try {
                        const err = JSON.parse(error.message);
                        errorMsg = err.status === 403 ? 'You are not authorized to delete entries' :
                                   err.status === 404 ? 'Entry not found' : err.message;
                    } catch (e) {
                        errorMsg = 'An unexpected error occurred. Please try again.';
                    }
                    alert(errorMsg);
                });
            }
        }
    });
});
