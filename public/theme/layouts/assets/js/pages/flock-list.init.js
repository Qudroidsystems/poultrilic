// Global variables
const perPage = 5;
let editlist = false;
let birdCountFilterVal = null;
let flockList = null;

// Utility functions
function formatDate(dateStr) {
    if (!dateStr) return '';
    return dateStr.split('T')[0];
}

function showError(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            confirmButtonText: 'OK'
        });
    } else {
        alert(message);
    }
}

function showSuccess(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: message,
            showConfirmButton: false,
            timer: 2000,
            showCloseButton: true
        });
    } else {
        alert(message);
    }
}

function ensureAxios() {
    if (typeof axios === 'undefined') {
        console.error('Axios is not defined');
        showError('Axios library is missing');
        return false;
    }
    return true;
}

function ensureSwal() {
    if (typeof Swal === 'undefined') {
        console.warn('SweetAlert2 not available, using alert');
        return false;
    }
    return true;
}

function clearAddFields() {
    const addIdField = document.getElementById('add-id-field');
    const addInitialBirdCount = document.getElementById('initial_bird_count');
    if (addIdField) addIdField.value = '';
    if (addInitialBirdCount) addInitialBirdCount.value = '';
}

function clearEditFields() {
    const editIdField = document.getElementById('edit-id-field');
    const editInitialBirdCountField = document.getElementById('edit-initial_bird_count');
    const editCurrentBirdCount = document.getElementById('edit-current_bird_count');
    if (editIdField) editIdField.value = '';
    if (editInitialBirdCountField) editInitialBirdCountField.value = '';
    if (editCurrentBirdCount) editCurrentBirdCount.value = '';
}

// Initialize List.js for flock table
function initializeList() {
    try {
        const flockListContainer = document.getElementById('flockList');
        if (!flockListContainer) {
            console.error('Flock list container (#flockList) not found in DOM');
            return false;
        }

        const flockTableBody = flockListContainer.querySelector('tbody.list');
        if (!flockTableBody) {
            console.error('Table body with class "list" not found in #flockList');
            return false;
        }

        const flockRowTemplate = document.getElementById('flockRowTemplate');
        if (!flockRowTemplate) {
            console.error('Flock row template (#flockRowTemplate) not found in DOM');
            return false;
        }

        const hasOnlyNoResult = flockTableBody.querySelector('tr.noresult') && flockTableBody.children.length === 1;
        if (hasOnlyNoResult) {
            console.warn('No flock data available, initializing empty list');
            flockTableBody.innerHTML = '';
        }

        const options = {
            valueNames: ['id', 'initial_bird_count', 'current_bird_count', 'created_at'],
            page: perPage,
            pagination: false,
            item: flockRowTemplate.innerHTML,
            listClass: 'list'
        };

        flockList = new List(flockListContainer, options);
        console.log('List.js initialized with', flockList.items.length, 'items');
        flockList.on('updated', function (e) {
            console.log('List updated, matching items:', e.matchingItems.length);
            const noResultElement = document.querySelector('.noresult');
            if (noResultElement) {
                noResultElement.style.display = e.matchingItems.length === 0 ? 'table-row' : 'none';
            }
            setTimeout(() => {
                refreshCallbacks();
                ischeckboxcheck();
                updateFlockChart();
            }, 100);
        });
        return true;
    } catch (error) {
        console.error('Error initializing List.js:', error);
        return false;
    }
}

// Update flock distribution chart
function updateFlockChart() {
    if (!window.flockChart) {
        console.warn('Flock chart instance not found');
        return;
    }
    const birdCountRanges = ['0-100', '101-200', '201-500', '501+'];
    const birdCountData = [0, 0, 0, 0];

    try {
        if (flockList && flockList.items) {
            flockList.items.forEach(item => {
                const values = item.values();
                const count = parseInt(values.initial_bird_count, 10) || 0;
                if (count <= 100) birdCountData[0]++;
                else if (count <= 200) birdCountData[1]++;
                else if (count <= 500) birdCountData[2]++;
                else birdCountData[3]++;
            });
        } else {
            console.warn('flockList or flockList.items not available, using DOM fallback');
            const rows = document.querySelectorAll('tbody.list tr:not(.noresult)');
            rows.forEach(row => {
                const countCell = row.querySelector('.initial_bird_count');
                if (countCell) {
                    const count = parseInt(countCell.textContent.trim(), 10) || 0;
                    if (count <= 100) birdCountData[0]++;
                    else if (count <= 200) birdCountData[1]++;
                    else if (count <= 500) birdCountData[2]++;
                    else birdCountData[3]++;
                }
            });
        }

        console.log('Flock chart data:', birdCountData);
        window.flockChart.data.labels = birdCountRanges;
        window.flockChart.data.datasets[0].data = birdCountData;
        window.flockChart.update();
        console.log('Flock chart updated successfully');
    } catch (err) {
        console.error('Error updating flock chart:', err);
    }
}

// Update week entries chart
function updateWeekChart(data) {
    if (!window.weekChart) {
        console.warn('Week chart instance not found');
        return;
    }
    try {
        const labels = data.weekChartData?.labels || [];
        const counts = data.weekChartData?.daily_entries_counts || [];
        console.log('Week chart data:', { labels, counts });

        if (!labels.length || !counts.length) {
            console.warn('No data available for weekChart, setting empty state');
            window.weekChart.data.labels = ['No Data'];
            window.weekChart.data.datasets[0].data = [0];
        } else {
            window.weekChart.data.labels = labels;
            window.weekChart.data.datasets[0].data = counts;
        }

        window.weekChart.update();
        console.log('Week chart updated successfully');
    } catch (err) {
        console.error('Error updating week chart:', err);
    }
}

// Update statistics display
function updateStats(flockStats, allFlocksStats) {
    const flockStatsContainer = document.getElementById('flock-stats');
    if (flockStatsContainer && flockStats) {
        flockStatsContainer.innerHTML = `
            <h5 class="card-title">Selected Flock Statistics</h5>
            <p>Total Weeks: ${flockStats.total_weeks || 0}</p>
            <p>Total Daily Entries: ${flockStats.total_daily_entries || 0}</p>
            <p>Total Egg Production: ${flockStats.total_egg_production || 0} eggs</p>
            <p>Total Mortality: ${flockStats.total_mortality || 0} birds</p>
            <p>Total Feeds Consumed: ${flockStats.total_feeds_consumed || 0} kg</p>
            <p>Average Daily Egg Production: ${(flockStats.avg_daily_egg_production || 0).toFixed(2)} eggs</p>
            <p>Average Daily Mortality: ${(flockStats.avg_daily_mortality || 0).toFixed(2)} birds</p>
            <p>Average Daily Feeds: ${(flockStats.avg_daily_feeds || 0).toFixed(2)} kg</p>
        `;
    } else if (flockStatsContainer) {
        flockStatsContainer.innerHTML = '<p class="text-muted">Select a flock to view statistics.</p>';
    }

    const allFlocksStatsContainer = document.getElementById('all-flocks-stats');
    if (allFlocksStatsContainer && allFlocksStats) {
        allFlocksStatsContainer.innerHTML = `
            <h5 class="card-title">All Flocks Statistics</h5>
            <p>Total Flocks: ${allFlocksStats.total_flocks || 0}</p>
            <p>Total Initial Bird Count: ${allFlocksStats.total_initial_bird_count || 0}</p>
            <p>Total Current Bird Count: ${allFlocksStats.total_current_bird_count || 0}</p>
            <p>Average Initial Bird Count: ${(allFlocksStats.avg_initial_bird_count || 0).toFixed(2)}</p>
            <p>Average Current Bird Count: ${(allFlocksStats.avg_current_bird_count || 0).toFixed(2)}</p>
            <p>Total Weeks: ${allFlocksStats.total_weeks || 0}</p>
            <p>Total Daily Entries: ${allFlocksStats.total_daily_entries || 0}</p>
            <p>Total Egg Production: ${allFlocksStats.total_egg_production || 0} eggs</p>
            <p>Total Mortality: ${allFlocksStats.total_mortality || 0} birds</p>
            <p>Total Feeds Consumed: ${allFlocksStats.total_feeds_consumed || 0} kg</p>
        `;
    }
}

// Filter data based on search and bird count
function filterData(url = null) {
    const search = document.getElementById('searchFlock')?.value || '';
    const birdCountFilter = document.getElementById('birdCountFilter')?.value || 'all';
    const fetchUrl = url || `/flocks?search=${encodeURIComponent(search)}&bird_count_filter=${encodeURIComponent(birdCountFilter)}`;

    fetch(fetchUrl, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => { throw new Error(JSON.stringify({ status: response.status, ...err })); });
        }
        return response.json();
    })
    .then(data => {
        if (flockList) {
            flockList.clear();
            flockList.add(data.flocks);
        }
        const paginationElement = document.getElementById('pagination-element');
        if (paginationElement) {
            paginationElement.innerHTML = data.pagination || '';
        }
        const totalBadge = document.querySelector('.badge.bg-dark-subtle');
        if (totalBadge) {
            totalBadge.textContent = data.total || 0;
        }
        updateFlockChart();
        updateStats(null, data.allFlocksStats);
        bindPagination();
        refreshCallbacks();
    })
    .catch(error => {
        console.error('Error filtering data:', error);
        let errorMsg = 'Failed to load flocks';
        try {
            const err = JSON.parse(error.message);
            errorMsg = err.status === 403 ? 'You are not authorized to view flocks' : err.error || err.message;
        } catch (e) {
            errorMsg = 'An unexpected error occurred. Please try again.';
        }
        showError(errorMsg);
    });
}

// Bind pagination links
function bindPagination() {
    document.querySelectorAll('#pagination-element .page-link').forEach(link => {
        link.removeEventListener('click', handlePaginationClick);
        link.addEventListener('click', handlePaginationClick);
    });
}

function handlePaginationClick(e) {
    e.preventDefault();
    const href = e.target.closest('.page-link').getAttribute('href');
    if (href && href !== '#') {
        filterData(href);
    }
}

// Fetch week entries for a flock
function fetchWeekEntries(flockId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) {
        console.error('CSRF token not found');
        showError('CSRF token missing. Please refresh the page.');
        return;
    }
    fetch(`/flocks/${flockId}/week-entries`, {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => { throw new Error(JSON.stringify({ status: response.status, ...err })); });
        }
        return response.json();
    })
    .then(data => {
        updateWeekChart(data);
        updateStats(data.flockStats, null);
    })
    .catch(error => {
        console.error('Error fetching week entries:', error);
        let errorMsg = 'Failed to load week entries';
        try {
            const err = JSON.parse(error.message);
            errorMsg = err.status === 403 ? 'You are not authorized to view week entries' :
                       err.status === 404 ? 'Flock not found' : err.error || err.message;
        } catch (e) {
            errorMsg = 'An unexpected error occurred. Please try again.';
        }
        showError(errorMsg);
    });
}

// Handle checkbox changes
function ischeckboxcheck() {
    const checkboxes = document.querySelectorAll('.chk-child.id');
    checkboxes.forEach(checkbox => {
        checkbox.removeEventListener('change', handleCheckboxChange);
        checkbox.addEventListener('change', handleCheckboxChange);
    });
}

function handleCheckboxChange(e) {
    const row = e.target.closest('tr');
    if (row) {
        row.classList.toggle('table-active', e.target.checked);
    }
    const checkedCount = document.querySelectorAll('.chk-child.id:checked').length;
    const removeActions = document.getElementById('remove-actions');
    if (removeActions) {
        removeActions.classList.toggle('d-none', checkedCount === 0);
    }
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        const allCheckboxes = document.querySelectorAll('.chk-child.id');
        checkAll.checked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
    }
}

// Refresh event listeners
function refreshCallbacks() {
    console.log('Refreshing callbacks...');
    const removeButtons = document.querySelectorAll('.remove-item-btn');
    const editButtons = document.querySelectorAll('.edit-item-btn');
    const rows = document.querySelectorAll('tr.list');

    console.log('Found', removeButtons.length, 'remove buttons,', editButtons.length, 'edit buttons,', rows.length, 'rows');

    removeButtons.forEach((btn, index) => {
        btn.removeEventListener('click', handleRemoveClick);
        btn.addEventListener('click', (e) => {
            console.log(`Remove button ${index} clicked`);
            handleRemoveClick(e);
        });
    });

    editButtons.forEach((btn, index) => {
        btn.removeEventListener('click', handleEditClick);
        btn.addEventListener('click', (e) => {
            console.log(`Edit button ${index} clicked`);
            handleEditClick(e);
        });
    });

    rows.forEach((row, index) => {
        row.removeEventListener('click', handleRowClick);
        row.addEventListener('click', (e) => {
            console.log(`Row ${index} clicked`);
            handleRowClick(e);
        });
    });
}

// Handle row click for fetching week entries
function handleRowClick(e) {
    if (e.target.closest('.edit-item-btn, .remove-item-btn, .chk-child, .btn-subtle-primary')) {
        return;
    }
    const checkbox = e.target.closest('tr').querySelector('.chk-child.id');
    if (!checkbox) return;
    const flockId = checkbox.value;
    document.querySelectorAll('tr').forEach(row => row.classList.remove('table-active'));
    e.target.closest('tr').classList.add('table-active');
    fetchWeekEntries(flockId);
}

// Handle edit button click
function handleEditClick(e) {
    e.preventDefault();
    e.stopPropagation();
    console.log('handleEditClick triggered');

    try {
        const tr = e.target.closest('tr');
        if (!tr) {
            console.error('Table row not found for edit button');
            showError('Could not find table row');
            return;
        }

        const checkbox = tr.querySelector('.chk-child.id');
        if (!checkbox) {
            console.error('Checkbox with class "chk-child id" not found in row');
            showError('Could not find checkbox in row');
            return;
        }

        const itemId = checkbox.getAttribute('data-id');
        if (!itemId || isNaN(parseInt(itemId))) {
            console.error('Invalid item ID:', itemId);
            showError('Cannot edit flock with invalid ID');
            if (flockList) {
                flockList.remove('id', itemId);
                flockList.reIndex();
                flockList.update();
            }
            tr.remove();
            return;
        }

        console.log('Edit clicked for ID:', itemId);

        editlist = true;
        const editIdField = document.getElementById('edit-id-field');
        const editInitialBirdCountField = document.getElementById('edit-initial_bird_count');
        const editCurrentBirdCount = document.getElementById('edit-current_bird_count');

        if (editIdField) editIdField.value = itemId;
        const initialBirdCountCell = tr.querySelector('.initial_bird_count');
        const currentBirdCountCell = tr.querySelector('.current_bird_count');

        if (editInitialBirdCountField && initialBirdCountCell) {
            editInitialBirdCountField.value = initialBirdCountCell.textContent.trim();
        } else {
            console.warn('Initial bird count field or cell not found');
        }

        if (editCurrentBirdCount && currentBirdCountCell) {
            editCurrentBirdCount.value = currentBirdCountCell.textContent.trim();
        } else {
            console.warn('Current bird count field or cell not found');
        }

        const editModal = document.getElementById('editFlockModal');
        if (editModal) {
            console.log('Opening editFlockModal for ID:', itemId);
            const modal = new bootstrap.Modal(editModal);
            modal.show();
        } else {
            console.error('Edit modal element (#editFlockModal) not found');
            showError('Edit modal not found');
        }
    } catch (error) {
        console.error('Error in handleEditClick:', error);
        showError('Failed to open edit modal');
    }
}

// Handle delete button click
function handleRemoveClick(e) {
    e.preventDefault();
    e.stopPropagation();
    console.log('handleRemoveClick triggered');

    try {
        const tr = e.target.closest('tr');
        if (!tr) {
            console.error('Table row not found for delete button');
            showError('Could not find table row');
            return;
        }

        const checkbox = tr.querySelector('.chk-child.id');
        if (!checkbox) {
            console.error('Checkbox with class "chk-child id" not found in row');
            showError('Could not find checkbox in row');
            return;
        }

        const itemId = checkbox.getAttribute('data-id');
        if (!itemId || isNaN(parseInt(itemId))) {
            console.error('Invalid item ID:', itemId);
            showError('Cannot delete flock with invalid ID');
            if (flockList) {
                flockList.remove('id', itemId);
                flockList.reIndex();
                flockList.update();
            }
            tr.remove();
            return;
        }

        console.log('Delete clicked for ID:', itemId);

        const deleteModal = document.getElementById('deleteRecordModal');
        const deleteButton = document.getElementById('delete-record');

        if (!deleteModal || !deleteButton) {
            console.error('Delete modal (#deleteRecordModal) or button (#delete-record) not found');
            showError('Delete modal or button not found');
            return;
        }

        const deleteHandler = (event) => {
            event.preventDefault();
            console.log('Delete button in modal clicked for ID:', itemId);
            if (!ensureAxios()) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!csrfToken) {
                console.error('CSRF token not found');
                showError('CSRF token missing. Please refresh the page.');
                return;
            }

            axios.delete(`/flocks/${itemId}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            })
            .then(() => {
                console.log('Deleted flock ID:', itemId);

                if (flockList) {
                    const item = flockList.items.find(i => {
                        const id = i._values.id || i.elm.querySelector('.chk-child.id')?.getAttribute('data-id');
                        return id === itemId;
                    });

                    if (item) {
                        flockList.remove('id', itemId);
                    } else {
                        console.warn('List.js item not found, removing DOM row');
                        tr.remove();
                    }

                    flockList.reIndex();
                    flockList.update();
                } else {
                    console.warn('flockList not initialized, removing DOM row');
                    tr.remove();
                }

                updateFlockChart();
                updateStats(null, null);

                const modal = bootstrap.Modal.getInstance(deleteModal);
                if (modal) modal.hide();

                showSuccess('Flock deleted successfully!');
            })
            .catch(error => {
                console.error('Error deleting flock:', error);
                const modal = bootstrap.Modal.getInstance(deleteModal);
                if (modal) modal.hide();

                let message = error.response?.data?.message || 'An error occurred';
                if (error.response?.status === 404) {
                    message = `Flock ID ${itemId} not found`;
                    console.warn('Removing stale row for ID:', itemId);
                    if (flockList) {
                        flockList.remove('id', itemId);
                        flockList.reIndex();
                        flockList.update();
                    }
                    tr.remove();
                }

                showError(message);
            });
        };

        deleteButton.removeEventListener('click', deleteHandler);
        deleteButton.addEventListener('click', deleteHandler, { once: true });

        console.log('Opening deleteRecordModal for ID:', itemId);
        const modal = new bootstrap.Modal(deleteModal);
        modal.show();
    } catch (error) {
        console.error('Error in handleRemoveClick:', error);
        showError('Failed to initiate delete');
    }
}

// Handle multiple deletions
function deleteMultiple() {
    const ids_array = [];
    const checkboxes = document.querySelectorAll('.chk-child.id:checked');

    checkboxes.forEach(checkbox => {
        const id = checkbox.getAttribute('data-id');
        if (id && !isNaN(parseInt(id))) {
            ids_array.push(id);
        } else {
            console.warn('Skipping invalid ID:', id);
        }
    });

    if (ids_array.length === 0) {
        showError('No flocks selected for deletion');
        return;
    }

    const confirmAction = () => {
        if (!ensureAxios()) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            console.error('CSRF token not found');
            showError('CSRF token missing. Please refresh the page.');
            return;
        }

        Promise.all(ids_array.map(id => {
            return axios.delete(`/flocks/${id}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            }).catch(error => {
                console.error(`Error deleting flock ${id}:`, error);
                return { error, id };
            });
        })).then(results => {
            let hasErrors = false;
            results.forEach((result, index) => {
                const id = ids_array[index];
                if (result && result.error) {
                    hasErrors = true;
                    if (result.error.response?.status === 404) {
                        console.warn(`Flock ${id} not found, removing row`);
                        const tr = document.querySelector(`[data-id="${id}"]`)?.closest('tr');
                        if (tr) tr.remove();
                        if (flockList) {
                            flockList.remove('id', id);
                        }
                    }
                } else {
                    if (flockList) {
                        const item = flockList.items.find(i => {
                            const itemId = i._values.id || i.elm.querySelector('.chk-child.id')?.getAttribute('data-id');
                            return itemId === id;
                        });
                        if (item) {
                            flockList.remove('id', id);
                        }
                    } else {
                        console.warn('flockList not initialized, removing DOM row');
                        const tr = document.querySelector(`[data-id="${id}"]`)?.closest('tr');
                        if (tr) tr.remove();
                    }
                }
            });

            if (flockList) {
                flockList.reIndex();
                flockList.update();
            }

            updateFlockChart();
            updateStats(null, null);

            showSuccess(hasErrors ? 'Some flocks could not be deleted.' : 'Flocks deleted successfully!');
        });
    };

    if (ensureSwal()) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'btn btn-primary w-xs me-2 mt-2',
            cancelButtonClass: 'btn btn-danger w-xs mt-2',
            confirmButtonText: 'Yes, delete it!',
            buttonsStyling: false,
            showCloseButton: true
        }).then(result => {
            if (result.isConfirmed) {
                confirmAction();
            }
        });
    } else {
        if (confirm('Are you sure you want to delete the selected flocks?')) {
            confirmAction();
        }
    }
}

// Main initialization
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing...');
    console.log('Chart.js available:', typeof Chart !== 'undefined');
    console.log('Axios available:', typeof axios !== 'undefined');
    console.log('List.js available:', typeof List !== 'undefined');
    console.log('Choices.js available:', typeof Choices !== 'undefined');

    // Initialize List.js
    if (!initializeList()) {
        console.error('Failed to initialize List.js');
        console.warn('Attaching event listeners manually as fallback');
        refreshCallbacks();
        ischeckboxcheck();
        updateFlockChart();
    }

    // Initialize flock chart
    const flockChartCanvas = document.getElementById('flockChart');
    if (flockChartCanvas) {
        try {
            console.log('Initializing flockChart');
            window.flockChart = new Chart(flockChartCanvas, {
                type: 'bar',
                data: {
                    labels: ['0-100', '101-200', '201-500', '501+'],
                    datasets: [{
                        label: 'Number of Flocks',
                        data: [],
                        backgroundColor: '#0d6efd',
                        borderColor: '#0d6efd',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Number of Flocks' } },
                        x: { title: { display: true, text: 'Initial Bird Count Range' } }
                    },
                    plugins: {
                        legend: { display: true },
                        title: { display: true, text: 'Flock Distribution by Initial Bird Count' }
                    }
                }
            });
            console.log('flockChart initialized successfully');
        } catch (error) {
            console.error('Error initializing flockChart:', error);
        }
    } else {
        console.error('flockChart canvas not found');
    }

    // Initialize week chart
    const weekChartCanvas = document.getElementById('weekChart');
    if (weekChartCanvas) {
        try {
            console.log('Initializing weekChart');
            window.weekChart = new Chart(weekChartCanvas, {
                type: 'bar',
                data: {
                    labels: ['No Data'],
                    datasets: [{
                        label: 'Daily Entries per Week',
                        data: [0],
                        backgroundColor: '#36A2EB',
                        borderColor: '#36A2EB',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Number of Daily Entries' } },
                        x: { title: { display: true, text: 'Week' } }
                    },
                    plugins: {
                        legend: { display: true },
                        title: { display: true, text: 'Week Entries for Selected Flock' }
                    }
                }
            });
            console.log('weekChart initialized successfully');
        } catch (error) {
            console.error('Error initializing weekChart:', error);
        }
    } else {
        console.error('weekChart canvas not found');
    }

    // Initialize Choices.js for bird count filter
    if (typeof Choices !== 'undefined') {
        const birdCountSelect = document.getElementById('birdCountFilter');
        if (birdCountSelect) {
            birdCountFilterVal = new Choices(birdCountSelect, {
                searchEnabled: true,
                removeItemButton: true
            });
        }
    } else {
        console.warn('Choices.js not available');
    }

    // Handle check all checkbox
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('click', function () {
            const checkboxes = document.querySelectorAll('.chk-child.id');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                checkbox.closest('tr').classList.toggle('table-active', this.checked);
            });
            const removeActions = document.getElementById('remove-actions');
            if (removeActions) {
                removeActions.classList.toggle('d-none', !this.checked);
            }
        });
    }

    // Handle add flock form submission
    const addForm = document.getElementById('add-flock-form');
    if (addForm) {
        addForm.addEventListener('submit', e => {
            e.preventDefault();
            console.log('Add form submitted');

            const errorMsg = document.getElementById('add-error-msg');
            const addInitialBirdCount = document.getElementById('initial_bird_count');
            if (errorMsg) errorMsg.classList.add('d-none');

            if (!addInitialBirdCount || !addInitialBirdCount.value || parseInt(addInitialBirdCount.value) < 0) {
                if (errorMsg) {
                    errorMsg.textContent = 'Please enter a valid initial bird count';
                    errorMsg.classList.remove('d-none');
                    setTimeout(() => errorMsg.classList.add('d-none'), 3000);
                }
                return;
            }

            if (!ensureAxios()) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!csrfToken) {
                console.error('CSRF token not found');
                showError('CSRF token missing. Please refresh the page.');
                return;
            }

            const payload = {
                initial_bird_count: parseInt(addInitialBirdCount.value),
                current_bird_count: parseInt(addInitialBirdCount.value),
                _token: csrfToken
            };
            console.log('Sending payload:', payload);

            axios.post('/flocks', payload)
            .then(response => {
                console.log('Add response:', response.data);

                const newFlock = {
                    id: response.data.id.toString(),
                    initial_bird_count: response.data.initial_bird_count.toString(),
                    current_bird_count: response.data.current_bird_count.toString(),
                    created_at: formatDate(response.data.created_at)
                };

                if (flockList) {
                    const existingItem = flockList.items.find(i => {
                        const id = i._values.id || i.elm.querySelector('.chk-child.id')?.getAttribute('data-id');
                        return id === newFlock.id;
                    });

                    if (existingItem) {
                        console.warn('Removing existing item with ID:', newFlock.id);
                        flockList.remove('id', newFlock.id);
                    }

                    flockList.add(newFlock);
                    flockList.reIndex();
                    flockList.update();
                } else {
                    console.warn('flockList not initialized, appending manually');
                    const tbody = document.querySelector('tbody.list');
                    if (tbody) {
                        const template = document.getElementById('flockRowTemplate').innerHTML;
                        const html = template
                            .replace('{id}', newFlock.id)
                            .replace('{initial_bird_count}', newFlock.initial_bird_count)
                            .replace('{current_bird_count}', newFlock.current_bird_count)
                            .replace('{created_at}', newFlock.created_at);
                        tbody.insertAdjacentHTML('beforeend', html);
                    }
                }

                updateFlockChart();
                updateStats(null, null);

                const modal = bootstrap.Modal.getInstance(document.getElementById('addFlockModal'));
                if (modal) modal.hide();

                showSuccess('Flock added successfully!');
                clearAddFields();
                refreshCallbacks();
                ischeckboxcheck();
            })
            .catch(error => {
                console.error('Error adding flock:', error);
                let message = error.response?.data?.message || 'Error adding flock';
                if (error.response?.status === 422 && error.response.data.errors) {
                    message = Object.values(error.response.data.errors).flat().join(', ');
                } else if (error.response?.status === 403) {
                    message = 'You are not authorized to create flocks';
                }
                if (errorMsg) {
                    errorMsg.textContent = message;
                    errorMsg.classList.remove('d-none');
                    setTimeout(() => errorMsg.classList.add('d-none'), 3000);
                }
            });
        });
    }

    // Handle edit flock form submission
    const editForm = document.getElementById('edit-flock-form');
    if (editForm) {
        editForm.addEventListener('submit', e => {
            e.preventDefault();
            console.log('Edit form submitted');

            const errorMsg = document.getElementById('edit-error-msg');
            if (errorMsg) errorMsg.classList.add('d-none');

            const editInitialBirdCountField = document.getElementById('edit-initial_bird_count');
            const editCurrentBirdCount = document.getElementById('edit-current_bird_count');
            const editIdField = document.getElementById('edit-id-field');

            if (!editInitialBirdCountField || !editInitialBirdCountField.value || parseInt(editInitialBirdCountField.value) < 0) {
                if (errorMsg) {
                    errorMsg.textContent = 'Please enter a valid initial bird count';
                    errorMsg.classList.remove('d-none');
                    setTimeout(() => errorMsg.classList.add('d-none'), 3000);
                }
                return;
            }

            if (!editCurrentBirdCount || !editCurrentBirdCount.value || parseInt(editCurrentBirdCount.value) < 0) {
                if (errorMsg) {
                    errorMsg.textContent = 'Please enter a valid current bird count';
                    errorMsg.classList.remove('d-none');
                    setTimeout(() => errorMsg.classList.add('d-none'), 3000);
                }
                return;
            }

            const itemId = editIdField.value;
            if (!itemId || isNaN(parseInt(itemId))) {
                console.error('Invalid item ID for edit:', itemId);
                if (errorMsg) {
                    errorMsg.textContent = 'Invalid flock ID';
                    errorMsg.classList.remove('d-none');
                    setTimeout(() => errorMsg.classList.add('d-none'), 3000);
                }
                return;
            }

            if (!ensureAxios()) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!csrfToken) {
                console.error('CSRF token not found');
                showError('CSRF token missing. Please refresh the page.');
                return;
            }

            const payload = {
                initial_bird_count: parseInt(editInitialBirdCountField.value),
                current_bird_count: parseInt(editCurrentBirdCount.value),
                _token: csrfToken
            };
            console.log('Sending edit payload for ID:', itemId, payload);

            axios.put(`/flocks/${itemId}`, payload)
            .then(response => {
                console.log('Update response:', response.data);

                if (flockList) {
                    const item = flockList.items.find(i => {
                        const id = i._values.id || i.elm.querySelector('.chk-child.id')?.getAttribute('data-id');
                        return id === itemId;
                    });

                    if (item) {
                        item.values({
                            id: response.data.id.toString(),
                            initial_bird_count: response.data.initial_bird_count.toString(),
                            current_bird_count: response.data.current_bird_count.toString(),
                            created_at: formatDate(response.data.created_at)
                        });

                        const checkbox = item.elm.querySelector('.chk-child.id');
                        const link = item.elm.querySelector('a');
                        if (checkbox) checkbox.setAttribute('data-id', response.data.id);
                        if (link) link.href = `/flocks/${response.data.id}/week-entries`;
                    } else {
                        console.warn('List.js item not found, updating DOM manually');
                        const tr = document.querySelector(`[data-id="${itemId}"]`)?.closest('tr');
                        if (tr) {
                            const initialCell = tr.querySelector('.initial_bird_count');
                            const currentCell = tr.querySelector('.current_bird_count');
                            const createdCell = tr.querySelector('.created_at');
                            const checkbox = tr.querySelector('.chk-child.id');
                            const link = tr.querySelector('a');

                            if (initialCell) initialCell.textContent = response.data.initial_bird_count;
                            if (currentCell) currentCell.textContent = response.data.current_bird_count;
                            if (createdCell) createdCell.textContent = formatDate(response.data.created_at);
                            if (checkbox) checkbox.setAttribute('data-id', response.data.id);
                            if (link) link.href = `/flocks/${response.data.id}/week-entries`;
                        }
                    }

                    flockList.reIndex();
                    flockList.update();
                } else {
                    console.warn('flockList not initialized, updating DOM manually');
                    const tr = document.querySelector(`[data-id="${itemId}"]`)?.closest('tr');
                    if (tr) {
                        const initialCell = tr.querySelector('.initial_bird_count');
                        const currentCell = tr.querySelector('.current_bird_count');
                        const createdCell = tr.querySelector('.created_at');
                        const checkbox = tr.querySelector('.chk-child.id');
                        const link = tr.querySelector('a');

                        if (initialCell) initialCell.textContent = response.data.initial_bird_count;
                        if (currentCell) currentCell.textContent = response.data.current_bird_count;
                        if (createdCell) createdCell.textContent = formatDate(response.data.created_at);
                        if (checkbox) checkbox.setAttribute('data-id', response.data.id);
                        if (link) link.href = `/flocks/${response.data.id}/week-entries`;
                    }
                }

                updateFlockChart();
                updateStats(null, null);

                const modal = bootstrap.Modal.getInstance(document.getElementById('editFlockModal'));
                if (modal) modal.hide();

                showSuccess('Flock updated successfully!');
                clearEditFields();
                refreshCallbacks();
                ischeckboxcheck();
            })
            .catch(error => {
                console.error('Error updating flock:', error);
                let message = error.response?.data?.message || 'Error updating flock';
                if (error.response?.status === 404) {
                    message = `Flock ID ${itemId} not found`;
                    console.warn('Removing stale row for ID:', itemId);
                    const tr = document.querySelector(`[data-id="${itemId}"]`)?.closest('tr');
                    if (tr) tr.remove();
                    if (flockList) {
                        flockList.remove('id', itemId);
                        flockList.reIndex();
                        flockList.update();
                    }
                }
                if (errorMsg) {
                    errorMsg.textContent = message;
                    errorMsg.classList.remove('d-none');
                    setTimeout(() => errorMsg.classList.add('d-none'), 3000);
                }
            });
        });
    }

    // Handle modal events
    const addModal = document.getElementById('addFlockModal');
    if (addModal) {
        addModal.addEventListener('show.bs.modal', () => {
            console.log('Opening addFlockModal...');
            const modalLabel = document.getElementById('addModalLabel');
            const addBtn = document.getElementById('add-btn');
            if (modalLabel) modalLabel.textContent = 'Add Flock';
            if (addBtn) addBtn.textContent = 'Add Flock';
        });
        addModal.addEventListener('hidden.bs.modal', () => {
            console.log('addFlockModal closed, clearing fields...');
            clearAddFields();
        });
    }

    const editModal = document.getElementById('editFlockModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', () => {
            console.log('Opening editFlockModal...');
            const modalLabel = document.getElementById('editModalLabel');
            const updateBtn = document.getElementById('update-btn');
            if (modalLabel) modalLabel.textContent = 'Edit Flock';
            if (updateBtn) updateBtn.textContent = 'Update';
        });
        editModal.addEventListener('hidden.bs.modal', () => {
            console.log('editFlockModal closed, clearing fields...');
            clearEditFields();
        });
    }

    // Initial setup
    refreshCallbacks();
    ischeckboxcheck();
    updateFlockChart();
    updateStats(null, null);
});