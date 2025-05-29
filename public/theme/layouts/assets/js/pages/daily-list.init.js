document.addEventListener('DOMContentLoaded', function () {
    console.log('daily-list.init.js: Script loaded');

    // Initialize Choices.js
    const dayFilter = document.getElementById('dayFilter');
    if (dayFilter) {
        try {
            new Choices(dayFilter, { removeItemButton: true, searchEnabled: true });
            console.log('Choices.js initialized for dayFilter');
        } catch (e) {
            console.error('Error initializing Choices.js:', e);
        }
    } else {
        console.warn('dayFilter: #dayFilter not found');
    }

    // Initialize List.js
    let dailyList = null;
    try {
        dailyList = new List('dailyList', {
            valueNames: ['day_number', 'daily_feeds', 'daily_mortality', 'current_birds', 'daily_egg_production', 'created_at'],
            listClass: 'list',
            searchClass: 'search',
            item: 'dailyRowTemplate'
        });
        console.log('List.js initialized');
    } catch (e) {
        console.error('Error initializing List.js:', e);
    }

    // Filter data (search, filter, pagination)
    window.filterData = function (url = null) {
        console.log('filterData: Starting', { url });
        const search = document.getElementById('searchDay').value;
        const dayFilterValue = dayFilter.value;
        const fetchUrl = url || `/daily-entries/${window.weekId}?search=${encodeURIComponent(search)}&day_filter=${encodeURIComponent(dayFilterValue)}`;
        fetch(fetchUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            return response.json();
        })
        .then(data => {
            if (dailyList) {
                dailyList.clear();
                dailyList.add(data.dailyEntries);
            }
            document.getElementById('pagination-element').innerHTML = data.pagination;
            document.querySelector('.badge.bg-dark-subtle').textContent = data.total;
            if (window.dailyChart) {
                window.dailyChart.data.labels = data.chartData.labels;
                window.dailyChart.data.datasets[0].data = data.chartData.daily_egg_production;
                window.dailyChart.update();
            }
            console.log('filterData: Data updated');
            // Rebind pagination clicks
            bindPagination();
        })
        .catch(error => {
            console.error('Error filtering data:', error);
            alert('Failed to filter data: ' + error.message);
        });
    };

    // Bind pagination clicks
    function bindPagination() {
        console.log('bindPagination: Binding clicks');
        document.querySelectorAll('#pagination-element .page-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                if (href && href !== '#') {
                    console.log('Pagination: Clicking', href);
                    window.filterData(href);
                }
            });
        });
    }

    // Initial pagination binding
    bindPagination();

    // Add daily entry
    const addForm = document.getElementById('add-daily-form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            e.preventDefault();
            console.log('Add modal: Form submitted');
            const formData = new FormData(this);
            fetch(`/daily-entries/${window.weekId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || `HTTP ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.id) {
                    if (dailyList) dailyList.add(data);
                    bootstrap.Modal.getInstance(document.getElementById('addDailyModal')).hide();
                    filterData();
                    alert('Daily entry added successfully');
                    addForm.reset();
                    document.getElementById('add-error-msg').classList.add('d-none');
                    console.log('Add modal: Entry added', data);
                }
            })
            .catch(error => {
                console.error('Error adding daily entry:', error);
                let errorMessage = error.message;
                try {
                    const err = JSON.parse(error.message);
                    if (err.errors) errorMessage = Object.values(err.errors).flat().join(', ');
                } catch (e) {}
                document.getElementById('add-error-msg').textContent = errorMessage;
                document.getElementById('add-error-msg').classList.remove('d-none');
            });
        });
    } else {
        console.warn('Add modal: #add-daily-form not found');
    }

    // Open edit modal
    document.addEventListener('click', function (e) {
        if (e.target.closest('.edit-item-btn')) {
            console.log('Edit modal: Opening');
            const row = e.target.closest('tr');
            const entryId = parseInt(row.querySelector('.chk-child').value);
            const dayNumber = row.querySelector('.day_number').textContent.replace('Day ', '');
            const dailyFeeds = parseFloat(row.querySelector('.daily_feeds').textContent) || 0;
            const dailyMortality = parseInt(row.querySelector('.daily_mortality').textContent) || 0;
            const dailyEggProduction = parseInt(row.querySelector('.daily_egg_production').textContent) || 0;

            document.getElementById('edit-id-field').value = entryId;
            document.getElementById('edit_day_number').value = dayNumber;
            document.getElementById('edit_daily_feeds').value = dailyFeeds;
            document.getElementById('edit_daily_mortality').value = dailyMortality;
            document.getElementById('edit_daily_egg_production').value = dailyEggProduction;

            ['available_feeds', 'total_feeds_consumed', 'sick_bay', 'total_mortality', 
             'daily_sold_egg', 'total_sold_egg', 'broken_egg', 'outstanding_egg', 
             'total_egg_in_farm', 'drugs', 'reorder_feeds'].forEach(field => {
                document.getElementById(`edit_${field}`).value = '';
            });

            bootstrap.Modal.getOrCreateInstance(document.getElementById('editDailyModal')).show();
        }
    });

    // Update daily entry
    const editForm = document.getElementById('edit-daily-form');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            console.log('Edit modal: Form submitted');
            const entryId = parseInt(document.getElementById('edit-id-field').value);
            const formData = new FormData(this);
            fetch(`/daily-entries/${window.weekId}/${entryId}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || `HTTP ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.id) {
                    if (dailyList) {
                        dailyList.remove('id', data.id);
                        dailyList.add(data);
                    }
                    bootstrap.Modal.getInstance(document.getElementById('editDailyModal')).hide();
                    filterData();
                    alert('Daily entry updated successfully');
                    document.getElementById('edit-error-msg').classList.add('d-none');
                    console.log('Edit modal: Entry updated', data);
                }
            })
            .catch(error => {
                console.error('Error updating daily entry:', error);
                let errorMessage = error.message;
                try {
                    const err = JSON.parse(error.message);
                    if (err.errors) errorMessage = Object.values(err.errors).flat().join(', ');
                } catch (e) {}
                document.getElementById('edit-error-msg').textContent = errorMessage;
                document.getElementById('edit-error-msg').classList.remove('d-none');
            });
        });
    } else {
        console.warn('Edit modal: #edit-daily-form not found');
    }

    // Open delete modal
    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-item-btn')) {
            console.log('Delete modal: Opening');
            const row = e.target.closest('tr');
            const entryId = parseInt(row.querySelector('.chk-child').value);
            document.getElementById('delete-record').dataset.entryId = entryId;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteRecordModal')).show();
        }
    });

    // Delete single entry
    const deleteButton = document.getElementById('delete-record');
    if (deleteButton) {
        deleteButton.addEventListener('click', function () {
            console.log('Delete modal: Deleting entry');
            const entryId = parseInt(this.dataset.entryId);
            fetch(`/daily-entries/${window.weekId}/${entryId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                return response.json();
            })
            .then(data => {
                if (dailyList) dailyList.remove('id', entryId);
                bootstrap.Modal.getInstance(document.getElementById('deleteRecordModal')).hide();
                filterData();
                alert(data.message || 'Daily entry deleted successfully');
                console.log('Delete modal: Entry deleted', entryId);
            })
            .catch(error => {
                console.error('Error deleting daily entry:', error);
                alert('Failed to delete entry: ' + error.message);
            });
        });
    } else {
        console.warn('Delete modal: #delete-record not found');
    }

    // Delete multiple entries
    window.deleteMultiple = function () {
        console.log('deleteMultiple: Starting');
        const selectedIds = Array.from(document.querySelectorAll('.chk-child:checked')).map(checkbox => parseInt(checkbox.value));
        if (selectedIds.length) {
            Promise.all(selectedIds.map(id =>
                fetch(`/daily-entries/${window.weekId}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.json();
                })
            ))
            .then(() => {
                if (dailyList) selectedIds.forEach(id => dailyList.remove('id', id));
                document.getElementById('remove-actions').classList.add('d-none');
                document.getElementById('checkAll').checked = false;
                filterData();
                alert('Daily entries deleted successfully');
                console.log('deleteMultiple: Entries deleted', selectedIds);
            })
            .catch(error => {
                console.error('Error deleting daily entries:', error);
                alert('Failed to delete entries: ' + error.message);
            });
        }
    };

    // Check all checkboxes
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            document.querySelectorAll('.chk-child').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            document.getElementById('remove-actions').classList.toggle('d-none', !this.checked);
            console.log('checkAll: Toggled', this.checked);
        });
    } else {
        console.warn('checkAll: #checkAll not found');
    }

    // Toggle delete button visibility
    document.querySelectorAll('.chk-child').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const anyChecked = document.querySelectorAll('.chk-child:checked').length > 0;
            document.getElementById('remove-actions').classList.toggle('d-none', !anyChecked);
            console.log('chk-child: Toggled', anyChecked);
        });
    });

    // Debug add button
    const addButton = document.querySelector('.add-btn');
    if (addButton) {
        addButton.addEventListener('click', () => {
            console.log('Add button: Clicked');
            const modal = document.getElementById('addDailyModal');
            if (modal) {
                console.log('Add modal: Found, showing');
                bootstrap.Modal.getOrCreateInstance(modal).show();
            } else {
                console.error('Add modal: #addDailyModal not found');
            }
        });
    } else {
        console.warn('Add button: .add-btn not found');
    }
});
