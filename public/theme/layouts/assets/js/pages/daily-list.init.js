document.addEventListener('DOMContentLoaded', function () {
    console.log('daily-list.init.js: Loaded');

    // CSRF Token Setup
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) {
        console.error('CSRF token not found. Ensure <meta name="csrf-token"> is in the HTML head.');
        alert('CSRF token not found. Please reload the page or contact support.');
        return;
    }
    console.log('CSRF Token:', csrfToken);

    // Verify weekId
    if (!window.weekId) {
        console.error('window.weekId is undefined. Ensure it is set in the Blade template.');
        alert('Week ID not found. Please reload the page or contact support.');
        return;
    }
    console.log('weekId:', window.weekId);

    // Filter Data
    window.filterData = function (url = null) {
        console.log('filterData called:', { url });
        const search = document.getElementById('searchDay').value;
        const dayFilter = document.getElementById('dayFilter').value;
        const fetchUrl = url || `/daily-entries/${window.weekId}?search=${encodeURIComponent(search)}&day_filter=${encodeURIComponent(dayFilter)}`;
        console.log('Fetching URL:', fetchUrl);
        fetch(fetchUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('filterData response status:', response.status);
            if (!response.ok) {
                return response.json().then(err => { throw new Error(JSON.stringify(err)); });
            }
            return response.json();
        })
        .then(data => {
            console.log('filterData response:', data);
            const tbody = document.querySelector('#dailyTable tbody');
            tbody.innerHTML = data.dailyEntries.map(entry => `
                <tr key="${entry.id}">
                    <td><div class="form-check"><input class="form-check-input chk-child" type="checkbox" name="chk_child" value="${entry.id}" data-id="${entry.id}"><label class="form-check-label"></label></div></td>
                    <td class="day_number">${entry.day_number}</td>
                    <td class="daily_feeds">${entry.daily_feeds}</td>
                    <td class="daily_mortality">${entry.daily_mortality}</td>
                    <td class="current_birds">${entry.current_birds}</td>
                    <td class="daily_egg_production">${entry.daily_egg_production}</td>
                    <td class="created_at">${entry.created_at}</td>
                    <td>
                        <div class="hstack gap-2">
                            <button type="button" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" title="Edit entry" data-id="${entry.id}"><i class="ph-pencil"></i></button>
                            <button type="button" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" title="Delete entry" data-id="${entry.id}"><i class="ph-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `).join('');
            document.getElementById('pagination-element').innerHTML = data.pagination;
            if (window.dailyChart) {
                window.dailyChart.data.labels = data.chartData.labels;
                window.dailyChart.data.datasets[0].data = data.chartData.daily_egg_production;
                window.dailyChart.update();
            }
            console.log('filterData: Table and chart updated');
            bindPagination();
        })
        .catch(error => {
            console.error('filterData error:', error);
            let errorMsg = 'Failed to fetch data';
            try {
                const err = JSON.parse(error.message);
                errorMsg = err.message || errorMsg;
            } catch (e) {
                errorMsg = error.message || errorMsg;
            }
            alert(`Error fetching data: ${errorMsg}`);
        });
    };

    // Bind Pagination
    function bindPagination() {
        console.log('bindPagination called');
        document.querySelectorAll('#pagination-element .page-link').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const href = link.getAttribute('href');
                if (href && href !== '#') {
                    console.log('Pagination click:', href);
                    window.filterData(href);
                }
            });
        });
    }
    bindPagination();

    // Add Entry
    const addForm = document.getElementById('add-daily-form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            e.preventDefault();
            try {
                console.log('Add form submit event triggered');
                if (!this.checkValidity()) {
                    console.log('Add form validation failed');
                    this.reportValidity();
                    document.getElementById('add-error-msg').textContent = 'Please fill out all required fields correctly.';
                    document.getElementById('add-error-msg').classList.remove('d-none');
                    return;
                }
                const formData = new FormData(this);
                console.log('Add form data:', Object.fromEntries(formData));
                console.log('Initiating fetch for add daily entry');
                fetch(`/daily-entries/${window.weekId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    console.log('Add fetch response status:', response.status);
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(JSON.stringify(err)); });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Add modal: Success', data);
                    bootstrap.Modal.getInstance(document.getElementById('addDailyModal')).hide();
                    window.filterData();
                    alert('Daily entry added successfully');
                    addForm.reset();
                    document.getElementById('add-error-msg').classList.add('d-none');
                })
                .catch(error => {
                    console.error('Add error:', error);
                    let errorMsg = 'Failed to add entry';
                    try {
                        const err = JSON.parse(error.message);
                        errorMsg = err.errors ? Object.values(err.errors).flat().join(', ') : err.message;
                    } catch (e) {
                        errorMsg = error.message || errorMsg;
                    }
                    console.error('Add error details:', errorMsg);
                    document.getElementById('add-error-msg').textContent = errorMsg;
                    document.getElementById('add-error-msg').classList.remove('d-none');
                });
            } catch (error) {
                console.error('Unexpected error in add form submit:', error);
                document.getElementById('add-error-msg').textContent = 'Unexpected error occurred. Please try again.';
                document.getElementById('add-error-msg').classList.remove('d-none');
            }
        });
    } else {
        console.warn('Add form not found');
        alert('Add form not found. Please reload the page or contact support.');
    }

    // Edit Modal
    document.addEventListener('click', function (e) {
        if (e.target.closest('.edit-item-btn')) {
            console.log('Edit modal: Opening');
            const row = e.target.closest('tr');
            const entryId = row.querySelector('.chk-child').value;
            console.log('Fetching entry:', entryId);
            fetch(`/daily-entries/${window.weekId}/${entryId}`, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Edit fetch response status:', response.status);
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(JSON.stringify(err)); });
                }
                return response.json();
            })
            .then(data => {
                console.log('Edit modal: Data fetched', data);
                document.getElementById('edit-id-field').value = data.id;
                document.getElementById('edit_day_number').value = data.day_number.replace('Day ', '');
                document.getElementById('edit_daily_feeds').value = data.daily_feeds;
                document.getElementById('edit_available_feeds').value = data.available_feeds || 0;
                document.getElementById('edit_total_feeds_consumed').value = data.total_feeds_consumed || 0;
                document.getElementById('edit_daily_mortality').value = data.daily_mortality;
                document.getElementById('edit_sick_bay').value = data.sick_bay || 0;
                document.getElementById('edit_total_mortality').value = data.total_mortality || 0;
                document.getElementById('edit_daily_egg_production').value = data.daily_egg_production;
                document.getElementById('edit_daily_sold_egg').value = data.daily_sold_egg || 0;
                document.getElementById('edit_total_sold_egg').value = data.total_sold_egg || 0;
                document.getElementById('edit_broken_egg').value = data.broken_egg || 0;
                document.getElementById('edit_outstanding_egg').value = data.outstanding_egg || 0;
                document.getElementById('edit_total_egg_in_farm').value = data.total_egg_in_farm || 0;
                document.getElementById('edit_drugs').value = data.drugs || '';
                document.getElementById('edit_reorder_feeds').value = data.reorder_feeds || '';
                bootstrap.Modal.getOrCreateInstance(document.getElementById('editDailyModal')).show();
            })
            .catch(error => {
                console.error('Edit fetch error:', error);
                let errorMsg = 'Failed to fetch entry';
                try {
                    const err = JSON.parse(error.message);
                    errorMsg = err.message || errorMsg;
                } catch (e) {
                    errorMsg = error.message || errorMsg;
                }
                alert(`Error fetching entry: ${errorMsg}`);
            });
        }
    });

    // Update Entry
    const editForm = document.getElementById('edit-daily-form');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            try {
                console.log('Edit form submit event triggered');
                if (!this.checkValidity()) {
                    console.log('Edit form validation failed');
                    this.reportValidity();
                    document.getElementById('edit-error-msg').textContent = 'Please fill out all required fields correctly.';
                    document.getElementById('edit-error-msg').classList.remove('d-none');
                    return;
                }
                const entryId = document.getElementById('edit-id-field').value;
                const formData = new FormData(this);
                console.log('Edit form data:', Object.fromEntries(formData));
                console.log('Initiating fetch for update daily entry');
                fetch(`/daily-entries/${window.weekId}/${entryId}`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    console.log('Edit fetch response status:', response.status);
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(JSON.stringify(err)); });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Edit modal: Success', data);
                    bootstrap.Modal.getInstance(document.getElementById('editDailyModal')).hide();
                    window.filterData();
                    alert('Daily entry updated successfully');
                    document.getElementById('edit-error-msg').classList.add('d-none');
                })
                .catch(error => {
                    console.error('Edit error:', error);
                    let errorMsg = 'Failed to update entry';
                    try {
                        const err = JSON.parse(error.message);
                        errorMsg = err.errors ? Object.values(err.errors).flat().join(', ') : err.message;
                    } catch (e) {
                        errorMsg = error.message || errorMsg;
                    }
                    console.error('Edit error details:', errorMsg);
                    document.getElementById('edit-error-msg').textContent = errorMsg;
                    document.getElementById('edit-error-msg').classList.remove('d-none');
                });
            } catch (error) {
                console.error('Unexpected error in edit form submit:', error);
                document.getElementById('edit-error-msg').textContent = 'Unexpected error occurred. Please try again.';
                document.getElementById('edit-error-msg').classList.remove('d-none');
            }
        });
    } else {
        console.warn('Edit form not found');
        alert('Edit form not found. Please reload the page or contact support.');
    }

    // Delete Modal
    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-item-btn')) {
            console.log('Delete modal: Opening');
            const entryId = e.target.closest('tr').querySelector('.chk-child').value;
            console.log('Delete entry ID:', entryId);
            document.getElementById('delete-record').dataset.entryId = entryId;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteRecordModal')).show();
        }
    });

    // Delete Entry
    const deleteButton = document.getElementById('delete-record');
    if (deleteButton) {
        deleteButton.addEventListener('click', function () {
            console.log('Delete modal: Deleting');
            const entryId = this.dataset.entryId;
            console.log('Deleting entry ID:', entryId);
            fetch(`/daily-entries/${window.weekId}/${entryId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Delete fetch response status:', response.status);
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(JSON.stringify(err)); });
                }
                return response.json();
            })
            .then(data => {
                console.log('Delete modal: Success', data);
                bootstrap.Modal.getInstance(document.getElementById('deleteRecordModal')).hide();
                window.filterData();
                alert('Daily entry deleted successfully');
            })
            .catch(error => {
                console.error('Delete error:', error);
                let errorMsg = 'Failed to delete entry';
                try {
                    const err = JSON.parse(error.message);
                    errorMsg = err.message || errorMsg;
                } catch (e) {
                    errorMsg = error.message || errorMsg;
                }
                alert(`Error deleting entry: ${errorMsg}`);
            });
        });
    } else {
        console.warn('Delete button not found');
        alert('Delete button not found. Please reload the page or contact support.');
    }

    // Check All
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            console.log('checkAll changed:', this.checked);
            document.querySelectorAll('.chk-child').forEach(cb => cb.checked = this.checked);
        });
    } else {
        console.warn('checkAll not found');
    }
});