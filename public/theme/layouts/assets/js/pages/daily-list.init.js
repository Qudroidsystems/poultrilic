document.addEventListener('DOMContentLoaded', function () {
    console.log('daily-list.init.js: Loaded');

    // CSRF Token Setup
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) {
        console.error('CSRF token not found');
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'CSRF token missing. Please refresh the page.'
        });
        return;
    }

    const CRATE_SIZE = 30;

    // Helper function to parse string egg format (e.g., "11 Cr 12PC") to total pieces
    function parseEggString(eggString) {
        if (!eggString || typeof eggString !== 'string' || !eggString.match(/\d+\s*Cr\s*\d+PC/)) {
            return 0;
        }
        const match = eggString.match(/(\d+)\s*Cr\s*(\d+)PC/);
        return match ? parseInt(match[1]) * CRATE_SIZE + parseInt(match[2]) : 0;
    }

    // Helper function to format total pieces to crates and pieces
    function toCratesAndPieces(totalPieces) {
        totalPieces = Math.max(0, parseInt(totalPieces) || 0);
        const crates = Math.floor(totalPieces / CRATE_SIZE);
        const pieces = totalPieces % CRATE_SIZE;
        return { crates, pieces, total: totalPieces };
    }

    // Helper function to format decimal numbers to 2 decimal places
    function formatDecimal(value) {
        return isNaN(value) || value === null ? '0.00' : Number(value).toFixed(2);
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
            outstandingDisplay.textContent = `${toCratesAndPieces(outstanding).crates} Cr ${toCratesAndPieces(outstanding).pieces}PC (${outstanding} pieces)`;
        }
    }

    // Fetch total_egg_in_farm for add modal
    function fetchTotalEggInFarm(modalPrefix) {
        fetch(`/daily-entries/${window.weekId}`, {
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
            const totalEggInFarm = parseEggString(data.total_egg_in_farm) || 0;
            const totalEggDisplay = document.getElementById(`${modalPrefix}_total_egg_in_farm`);
            if (totalEggDisplay) {
                totalEggDisplay.textContent = `${toCratesAndPieces(totalEggInFarm).crates} Cr ${toCratesAndPieces(totalEggInFarm).pieces}PC (${totalEggInFarm} pieces)`;
            }
        })
        .catch(error => {
            console.error('Fetch total_egg_in_farm error:', error);
            displayError(`${modalPrefix}-error-msg`, 'Failed to load total egg in farm');
        });
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
            console.log('filterData: Received data', data);
            const tbody = document.querySelector('#dailyTable tbody');
            if (!tbody) {
                console.error('Table body not found');
                return;
            }
            tbody.innerHTML = data.dailyEntries.map(entry => `
                <tr key="${entry.id}">
                    <td><input type="checkbox" class="chk-child" value="${entry.id}" data-id="${entry.id}"></td>
                    <td class="day_number">${entry.day_number}</td>
                    <td class="daily_feeds">${entry.daily_feeds}</td>
                    <td class="daily_mortality">${entry.daily_mortality}</td>
                    <td class="current_birds">${entry.current_birds}</td>
                    <td class="daily_egg_production">${entry.daily_egg_production}</td>
                    <td class="daily_sold_egg">${entry.daily_sold_egg}</td>
                    <td class="broken_egg">${entry.broken_egg}</td>
                    <td class="outstanding_egg">${entry.outstanding_egg}</td>
                    <td class="total_egg_in_farm">${entry.total_egg_in_farm}</td>
                    <td class="created_at">${entry.created_at}</td>
                    <td>
                        <div class="hstack gap-2">
                            <button type="button" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" title="Edit entry"><i class="ph-pencil"></i></button>
                            <button type="button" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" title="Delete entry"><i class="ph-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `).join('');
            const paginationElement = document.getElementById('pagination-element');
            if (paginationElement) {
                paginationElement.innerHTML = data.pagination;
            }
            if (window.dailyChart) {
                window.dailyChart.data.labels = data.chartData.labels;
                window.dailyChart.data.datasets[0].data = data.dailyEntries.map(entry => parseEggString(entry.daily_egg_production));
                window.dailyChart.update();
            }
            console.log('filterData: Updated');
            bindPagination();
            updateDeleteButtonVisibility();
        })
        .catch(error => {
            console.error('filterData error:', error);
            let errorMsg = 'Failed to load data';
            try {
                const err = JSON.parse(error.message);
                errorMsg = err.status === 404 ? 'Entries not found' : err.message;
            } catch (e) {
                errorMsg = 'An unexpected error occurred. Please try again.';
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMsg
            });
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

    // Update Delete Button Visibility
    function updateDeleteButtonVisibility() {
        const checkboxes = document.querySelectorAll('.chk-child:checked');
        const deleteButton = document.getElementById('remove-actions');
        if (checkboxes.length > 0) {
            deleteButton.classList.remove('d-none');
        } else {
            deleteButton.classList.add('d-none');
        }
    }

    // Check All Checkbox
    document.getElementById('checkAll')?.addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('.chk-child');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateDeleteButtonVisibility();
    });

    // Individual Checkbox Change
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('chk-child')) {
            updateDeleteButtonVisibility();
        }
    });

    // Add Modal Open
    document.querySelector('[data-bs-target="#addDailyModal"]').addEventListener('click', () => {
        console.log('Add modal: Opening');
        const addForm = document.getElementById('add-daily-form');
        if (addForm) {
            addForm.reset();
            document.getElementById('add_outstanding_egg').textContent = '0 Cr 0PC (0 pieces)';
            fetchTotalEggInFarm('add');
        }
    });

    // Add Entry
    const addForm = document.getElementById('add-daily-form');
    if (addForm) {
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
            const dailyFeeds = parseFloat(formData.get('daily_feeds')) || 0;
            const availableFeeds = parseFloat(formData.get('available_feeds')) || 0;
            const reorderFeeds = parseFloat(formData.get('reorder_feeds')) || 0;

            if (isNaN(dailyEggProductionCrates) || isNaN(dailyEggProductionPieces) ||
                isNaN(dailySoldEggCrates) || isNaN(dailySoldEggPieces) ||
                isNaN(brokenEggCrates) || isNaN(brokenEggPieces) ||
                isNaN(dailyFeeds) || isNaN(availableFeeds)) {
                displayError('add-error-msg', 'Invalid input for fields. Please enter valid numbers.');
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
                daily_feeds: dailyFeeds,
                available_feeds: availableFeeds,
                daily_mortality: parseInt(formData.get('daily_mortality')) || 0,
                sick_bay: parseInt(formData.get('sick_bay')) || 0,
                daily_egg_production_crates: dailyEggProductionCrates,
                daily_egg_production_pieces: dailyEggProductionPieces,
                daily_sold_egg_crates: dailySoldEggCrates,
                daily_sold_egg_pieces: dailySoldEggPieces,
                broken_egg_crates: brokenEggCrates,
                broken_egg_pieces: brokenEggPieces,
                drugs: formData.get('drugs') || '',
                reorder_feeds: reorderFeeds
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
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Daily entry added successfully'
                });
                addForm.reset();
                clearError('add-error-msg');
                document.getElementById('add_outstanding_egg').textContent = '0 Cr 0PC (0 pieces)';
                document.getElementById('add_total_egg_in_farm').textContent = '0 Cr 0PC (0 pieces)';
            })
            .catch(error => {
                console.error('Add error:', error);
                let errorMsg = 'Failed to add entry';
                try {
                    const err = JSON.parse(error.message);
                    errorMsg = err.status === 422 ? Object.values(err.errors).flat().join(', ') : err.message;
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
                document.getElementById('edit_day_number').value = data.day_number || '';
                document.getElementById('edit_daily_feeds').value = formatDecimal(data.daily_feeds || 0);
                document.getElementById('edit_available_feeds').value = formatDecimal(data.available_feeds || 0);
                document.getElementById('edit_daily_mortality').value = data.daily_mortality || 0;
                document.getElementById('edit_sick_bay').value = data.sick_bay || 0;
                const dailyEggProduction = toCratesAndPieces(parseEggString(data.daily_egg_production) || 0);
                document.getElementById('edit_daily_egg_production_crates').value = dailyEggProduction.crates;
                document.getElementById('edit_daily_egg_production_pieces').value = dailyEggProduction.pieces;
                const dailySoldEgg = toCratesAndPieces(parseEggString(data.daily_sold_egg) || 0);
                document.getElementById('edit_daily_sold_egg_crates').value = dailySoldEgg.crates;
                document.getElementById('edit_daily_sold_egg_pieces').value = dailySoldEgg.pieces;
                const brokenEgg = toCratesAndPieces(data.broken_egg || 0);
                document.getElementById('edit_broken_egg_crates').value = brokenEgg.crates;
                document.getElementById('edit_broken_egg_pieces').value = brokenEgg.pieces;
                document.getElementById('edit_drugs').value = data.drugs || '';
                document.getElementById('edit_reorder_feeds').value = formatDecimal(data.reorder_feeds || 0);
                document.getElementById('edit_outstanding_egg').textContent = data.outstanding_egg || '0 Cr 0PC (0 pieces)';
                document.getElementById('edit_total_egg_in_farm').textContent = data.total_egg_in_farm || '0 Cr 0PC (0 pieces)';
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
                    errorMsg = err.status === 404 ? 'Entry not found' : err.message;
                } catch (e) {
                    errorMsg = 'An unexpected error occurred. Please try again.';
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
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
            const dailyFeeds = parseFloat(formData.get('daily_feeds')) || 0;
            const availableFeeds = parseFloat(formData.get('available_feeds')) || 0;
            const reorderFeeds = parseFloat(formData.get('reorder_feeds')) || 0;

            if (isNaN(dailyEggProductionCrates) || isNaN(dailyEggProductionPieces) ||
                isNaN(dailySoldEggCrates) || isNaN(dailySoldEggPieces) ||
                isNaN(brokenEggCrates) || isNaN(brokenEggPieces) ||
                isNaN(dailyFeeds) || isNaN(availableFeeds)) {
                displayError('edit-error-msg', 'Invalid input for fields. Please enter valid numbers.');
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
                daily_feeds: dailyFeeds,
                available_feeds: availableFeeds,
                daily_mortality: parseInt(formData.get('daily_mortality')) || 0,
                sick_bay: parseInt(formData.get('sick_bay')) || 0,
                daily_egg_production_crates: dailyEggProductionCrates,
                daily_egg_production_pieces: dailyEggProductionPieces,
                daily_sold_egg_crates: dailySoldEggCrates,
                daily_sold_egg_pieces: dailySoldEggPieces,
                broken_egg_crates: brokenEggCrates,
                broken_egg_pieces: brokenEggPieces,
                drugs: formData.get('drugs') || '',
                reorder_feeds: reorderFeeds
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
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Daily entry updated successfully'
                });
                clearError('edit-error-msg');
            })
            .catch(error => {
                console.error('Edit error:', error);
                let errorMsg = 'Failed to update entry';
                try {
                    const err = JSON.parse(error.message);
                    errorMsg = err.status === 422 ? Object.values(err.errors).flat().join(', ') :
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
            const deleteModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteRecordModal'));
            const deleteButton = document.getElementById('delete-record');
            
            // Set up delete button click handler
            const deleteHandler = () => {
                console.log('Delete modal: Deleting entry', entryId);
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
                    deleteModal.hide();
                    window.filterData();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Daily entry deleted successfully'
                    });
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    let errorMsg = 'Failed to delete entry';
                    try {
                        const err = JSON.parse(error.message);
                        errorMsg = err.status === 404 ? 'Entry not found' : err.message;
                    } catch (e) {
                        errorMsg = 'An unexpected error occurred. Please try again.';
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMsg
                    });
                });
                // Remove the event listener after execution to prevent multiple bindings
                deleteButton.removeEventListener('click', deleteHandler);
            };

            deleteButton.addEventListener('click', deleteHandler);
            deleteModal.show();
        }
    });

    // Multiple Delete
    window.deleteMultiple = function () {
        const checkboxes = document.querySelectorAll('.chk-child:checked');
        const entryIds = Array.from(checkboxes).map(checkbox => checkbox.value);
        if (entryIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: 'No entries selected for deletion.'
            });
            return;
        }

        console.log('Delete multiple: Selected entries', entryIds);
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete ${entryIds.length} daily entr${entryIds.length > 1 ? 'ies' : 'y'}. This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) {
                const deletePromises = entryIds.map(entryId => 
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
                            return response.json().then(err => ({ success: false, error: err }));
                        }
                        return response.json().then(data => ({ success: true, data }));
                    })
                );

                Promise.all(deletePromises)
                    .then(results => {
                        const errors = results.filter(result => !result.success).map(result => result.error);
                        window.filterData();
                        if (errors.length > 0) {
                            console.error('Delete multiple errors:', errors);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: `Some entries could not be deleted: ${errors.map(err => err.message).join(', ')}`
                            });
                        } else {
                            console.log('Delete multiple: Success');
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: `${entryIds.length} daily entr${entryIds.length > 1 ? 'ies' : 'y'} deleted successfully`
                            });
                            document.getElementById('checkAll').checked = false;
                            updateDeleteButtonVisibility();
                        }
                    })
                    .catch(error => {
                        console.error('Delete multiple error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An unexpected error occurred while deleting entries. Please try again.'
                        });
                    });
            }
        });
    };

    // Initialize filter on page load
    window.filterData();
});