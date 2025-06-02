document.addEventListener('DOMContentLoaded', function () {
    console.log('daily-list.init.js: Loaded');

    // CSRF Token Setup
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) console.error('CSRF token not found');

    // Filter Data
    window.filterData = function (url = null) {
        console.log('filterData:', { url });
        const search = document.getElementById('searchDay').value;
        const dayFilter = document.getElementById('dayFilter').value;
        const fetchUrl = url || `/daily-entries/${window.weekId}?search=${encodeURIComponent(search)}&day_filter=${encodeURIComponent(dayFilter)}`;
        fetch(fetchUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            const tbody = document.getElementById('dailyList');
            tbody.innerHTML = data.dailyEntries.map(entry => `
                <tr>
                    <td><input type="checkbox" class="chk-child" value="${entry.id}" data-id="${entry.id}"></td>
                    <td class="day_number">${entry.day_number}</td>
                    <td class="daily_feeds">${entry.daily_feeds}</td>
                    <td class="daily_mortality">${entry.daily_mortality}</td>
                    <td class="current_birds">${entry.current_birds}</td>
                    <td class="daily_egg_production">${entry.daily_egg_production}</td>
                    <td class="created_at">${entry.created_at}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-secondary edit-item-btn" data-id="${entry.id}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" data-id="${entry.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            document.getElementById('pagination-element').innerHTML = data.pagination;
            if (window.dailyChart) {
                window.dailyChart.data.labels = data.chartData.labels;
                window.dailyChart.data.datasets[0].data = data.chartData.daily_egg_production;
                window.dailyChart.update();
            }
            console.log('filterData: Updated');
            bindPagination();
        })
        .catch(error => console.error('filterData error:', error));
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
        addForm.addEventListener('submit', function (e) {
            e.preventDefault();
            console.log('Add modal: Submit');
            const formData = new FormData(this);
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
                } catch (e) {}
                document.getElementById('add-error-msg').textContent = errorMsg;
                document.getElementById('add-error-msg').classList.remove('d-none');
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
            fetch(`/daily-entries/${window.weekId}/${entryId}`, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
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
            .catch(error => console.error('Edit fetch error:', error));
        }
    });

    // Update Entry
    const editForm = document.getElementById('edit-daily-form');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            console.log('Edit modal: Submit');
            const entryId = document.getElementById('edit-id-field').value;
            const formData = new FormData(this);
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
                } catch (e) {}
                document.getElementById('edit-error-msg').textContent = errorMsg;
                document.getElementById('edit-error-msg').classList.remove('d-none');
            });
        });
    } else {
        console.warn('Edit form not found');
    }

    // Delete Modal
    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-item-btn')) {
            console.log('Delete modal: Opening');
            const entryId = e.target.closest('tr').querySelector('.chk-child').value;
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
            fetch(`/daily-entries/${window.weekId}/${entryId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                console.log('Delete modal: Success', data);
                bootstrap.Modal.getInstance(document.getElementById('deleteRecordModal')).hide();
                window.filterData();
                alert('Daily entry deleted successfully');
            })
            .catch(error => console.error('Delete error:', error));
        });
    } else {
        console.warn('Delete button not found');
    }

    // Check All
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            document.querySelectorAll('.chk-child').forEach(cb => cb.checked = this.checked);
            console.log('checkAll:', this.checked);
        });
    } else {
        console.warn('checkAll not found');
    }
});