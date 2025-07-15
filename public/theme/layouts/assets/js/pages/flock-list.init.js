const perPage = 5;
let editlist = false;
let birdCountFilterVal = null;

const options = {
    valueNames: ['id', 'initial_bird_count', 'current_bird_count', 'created_at'],
    page: perPage,
    pagination: false,
    item: document.getElementById('flockRowTemplate').innerHTML
};

let flockList;

function initializeList() {
    try {
        flockList = new List('flockList', options);
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

function formatDate(dateStr) {
    if (!dateStr) return '';
    return dateStr.split('T')[0];
}

function updateFlockChart() {
    if (!window.flockChart) {
        console.warn('Flock chart instance not found');
        return;
    }
    const birdCountRanges = ['0-100', '101-200', '201-500', '501+'];
    const birdCountData = [0, 0, 0, 0];
    
    if (flockList && flockList.items) {
        flockList.items.forEach(item => {
            const values = item.values();
            const count = parseInt(values.initial_bird_count, 10) || 0;
            if (count <= 100) birdCountData[0]++;
            else if (count <= 200) birdCountData[1]++;
            else if (count <= 500) birdCountData[2]++;
            else birdCountData[3]++;
        });
    }
    
    console.log('Flock chart data:', birdCountData);
    window.flockChart.data.labels = birdCountRanges;
    window.flockChart.data.datasets[0].data = birdCountData;
    try {
        window.flockChart.update();
        console.log('Flock chart updated successfully');
    } catch (err) {
        console.error('Error updating flock chart:', err);
    }
}

function updateWeekChart(data) {
    if (!window.weekChart) {
        console.warn('Week chart instance not found');
        return;
    }
    window.weekChart.data.labels = data.weekChartData.labels;
    window.weekChart.data.datasets[0].data = data.weekChartData.daily_entries_counts;
    try {
        window.weekChart.update();
        console.log('Week chart updated successfully');
    } catch (err) {
        console.error('Error updating week chart:', err);
    }
}

function updateStats(flockStats, allFlocksStats) {
    const flockStatsContainer = document.getElementById('flock-stats');
    if (flockStatsContainer && flockStats) {
        flockStatsContainer.innerHTML = `
            <h5 class="card-title">Selected Flock Statistics</h5>
            <p>Total Weeks: ${flockStats.total_weeks}</p>
            <p>Total Daily Entries: ${flockStats.total_daily_entries}</p>
            <p>Total Egg Production: ${flockStats.total_egg_production} eggs</p>
            <p>Total Mortality: ${flockStats.total_mortality} birds</p>
            <p>Total Feeds Consumed: ${flockStats.total_feeds_consumed} kg</p>
            <p>Average Daily Egg Production: ${flockStats.avg_daily_egg_production.toFixed(2)} eggs</p>
            <p>Average Daily Mortality: ${flockStats.avg_daily_mortality.toFixed(2)} birds</p>
            <p>Average Daily Feeds: ${flockStats.avg_daily_feeds.toFixed(2)} kg</p>
        `;
    } else if (flockStatsContainer) {
        flockStatsContainer.innerHTML = '<p class="text-muted">Select a flock to view statistics.</p>';
    }

    const allFlocksStatsContainer = document.getElementById('all-flocks-stats');
    if (allFlocksStatsContainer && allFlocksStats) {
        allFlocksStatsContainer.innerHTML = `
            <h5 class="card-title">All Flocks Statistics</h5>
            <p>Total Flocks: ${allFlocksStats.total_flocks}</p>
            <p>Total Initial Bird Count: ${allFlocksStats.total_initial_bird_count}</p>
            <p>Total Current Bird Count: ${allFlocksStats.total_current_bird_count}</p>
            <p>Average Initial Bird Count: ${allFlocksStats.avg_initial_bird_count.toFixed(2)}</p>
            <p>Average Current Bird Count: ${allFlocksStats.avg_current_bird_count.toFixed(2)}</p>
            <p>Total Weeks: ${allFlocksStats.total_weeks}</p>
            <p>Total Daily Entries: ${allFlocksStats.total_daily_entries}</p>
            <p>Total Egg Production: ${allFlocksStats.total_egg_production} eggs</p>
            <p>Total Mortality: ${allFlocksStats.total_mortality} birds</p>
            <p>Total Feeds Consumed: ${allFlocksStats.total_feeds_consumed} kg</p>
        `;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing...');
    
    if (!initializeList()) {
        console.error('Failed to initialize List.js');
        return;
    }

    // Initialize Flock Chart
    const flockChartCanvas = document.getElementById('flockChart');
    if (flockChartCanvas) {
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
    }

    // Initialize Week Chart
    let weekChart = null;
    const weekChartCanvas = document.getElementById('weekChart');
    if (weekChartCanvas) {
        window.weekChart = new Chart(weekChartCanvas, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Daily Entries per Week',
                    data: [],
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
    }

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
    
    refreshCallbacks();
    ischeckboxcheck();
    updateFlockChart();
    updateStats(null, null); // Initialize stats containers
});

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
        flockList.clear();
        flockList.add(data.flocks);
        document.getElementById('pagination-element').innerHTML = data.pagination;
        document.querySelector('.badge.bg-dark-subtle').textContent = data.total;
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
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMsg,
                confirmButtonText: 'OK'
            });
        } else {
            alert(errorMsg);
        }
    });
}

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
        window.filterData(href);
    }
}

function fetchWeekEntries(flockId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) {
        console.error('CSRF token not found');
        alert('CSRF token missing. Please refresh the page.');
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
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMsg,
                confirmButtonText: 'OK'
            });
        } else {
            alert(errorMsg);
        }
    });
}

const checkAll = document.getElementById('checkAll');
if (checkAll) {
    checkAll.addEventListener('click', function () {
        const checkboxes = document.querySelectorAll('.chk-child');
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

const addIdField = document.getElementById('add-id-field');
const addInitialBirdCount = document.getElementById('initial_bird_count');
const editIdField = document.getElementById('edit-id-field');
const editInitialBirdCountField = document.getElementById('edit-initial_bird_count');
const editCurrentBirdCount = document.getElementById('edit-current_bird_count');

function ensureAxios() {
    if (typeof axios === 'undefined') {
        console.error('Axios is not defined');
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Configuration Error',
                text: 'Axios library is missing',
                confirmButtonText: 'OK'
            });
        } else {
            alert('Axios library is missing');
        }
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

function ischeckboxcheck() {
    const checkboxes = document.querySelectorAll('.chk-child');
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
    const checkedCount = document.querySelectorAll('.chk-child:checked').length;
    const removeActions = document.getElementById('remove-actions');
    if (removeActions) {
        removeActions.classList.toggle('d-none', checkedCount === 0);
    }
    if (checkAll) {
        const allCheckboxes = document.querySelectorAll('.chk-child');
        checkAll.checked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
    }
}

function refreshCallbacks() {
    console.log('Refreshing callbacks...');
    const removeButtons = document.querySelectorAll('.remove-item-btn');
    const editButtons = document.querySelectorAll('.edit-item-btn');
    const rows = document.querySelectorAll('tr');
    
    console.log('Found', removeButtons.length, 'remove buttons and', editButtons.length, 'edit buttons');
    
    removeButtons.forEach(btn => {
        btn.removeEventListener('click', handleRemoveClick);
        btn.addEventListener('click', handleRemoveClick);
    });
    
    editButtons.forEach(btn => {
        btn.removeEventListener('click', handleEditClick);
        btn.addEventListener('click', handleEditClick);
    });

    rows.forEach(row => {
        row.removeEventListener('click', handleRowClick);
        row.addEventListener('click', handleRowClick);
    });
}

function handleRowClick(e) {
    if (e.target.closest('.edit-item-btn, .remove-item-btn, .chk-child, .btn-subtle-primary')) {
        return;
    }
    const flockId = e.target.closest('tr').querySelector('.chk-child').value;
    document.querySelectorAll('tr').forEach(row => row.classList.remove('table-active'));
    e.target.closest('tr').classList.add('table-active');
    fetchWeekEntries(flockId);
}

function handleRemoveClick(e) {
    e.preventDefault();
    e.stopPropagation();
    
    try {
        const tr = e.target.closest('tr');
        if (!tr) {
            console.error('Could not find table row');
            return;
        }
        
        const checkbox = tr.querySelector('.chk-child');
        if (!checkbox) {
            console.error('Could not find checkbox in row');
            return;
        }
        
        const itemId = checkbox.getAttribute('data-id');
        if (!itemId || isNaN(parseInt(itemId))) {
            console.error('Invalid item ID:', itemId);
            if (ensureSwal()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Flock ID',
                    text: 'Cannot delete flock with invalid ID',
                    confirmButtonText: 'OK'
                });
            } else {
                alert('Cannot delete flock with invalid ID');
            }
            tr.remove();
            flockList.reIndex();
            flockList.update();
            return;
        }
        
        console.log('Remove clicked for ID:', itemId);
        
        const deleteModal = document.getElementById('deleteRecordModal');
        const deleteButton = document.getElementById('delete-record');
        
        if (!deleteModal || !deleteButton) {
            console.error('Delete modal or button not found');
            return;
        }
        
        const deleteHandler = (event) => {
            event.preventDefault();
            if (!ensureAxios()) return;
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                return;
            }
            
            axios.delete(`/flocks/${itemId}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken.content }
            }).then(() => {
                console.log('Deleted flock ID:', itemId);
                
                if (flockList) {
                    const item = flockList.items.find(i => {
                        const id = i._values.id || i.elm.querySelector('.chk-child')?.getAttribute('data-id');
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
                }
                
                updateFlockChart();
                updateStats(null, null);
                
                const modal = bootstrap.Modal.getInstance(deleteModal);
                if (modal) modal.hide();
                
                if (ensureSwal()) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted successfully!',
                        showConfirmButton: false,
                        timer: 2000,
                        showCloseButton: true
                    });
                } else {
                    alert('Flock deleted successfully!');
                }
            }).catch(error => {
                console.error('Error deleting flock:', error);
                const modal = bootstrap.Modal.getInstance(deleteModal);
                if (modal) modal.hide();
                
                let message = error.response?.data?.message || error.message || 'An error occurred';
                if (error.response?.status === 404) {
                    message = `Flock ID ${itemId} not found`;
                    console.warn('Removing stale row for ID:', itemId);
                    tr.remove();
                    flockList.reIndex();
                    flockList.update();
                }
                
                if (ensureSwal()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Deleting Flock',
                        text: message,
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert('Error deleting flock: ' + message);
                }
            });
        };
        
        deleteButton.removeEventListener('click', deleteHandler);
        deleteButton.addEventListener('click', deleteHandler, { once: true });
        
        const modal = new bootstrap.Modal(deleteModal);
        modal.show();
    } catch (error) {
        console.error('Error in remove-item-btn click:', error);
        if (ensureSwal()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to initiate delete',
                confirmButtonText: 'OK'
            });
        } else {
            alert('Failed to initiate delete');
        }
    }
}

function handleEditClick(e) {
    e.preventDefault();
    e.stopPropagation();
    
    try {
        const tr = e.target.closest('tr');
        if (!tr) {
            console.error('Could not find table row');
            return;
        }
        
        const checkbox = tr.querySelector('.chk-child');
        if (!checkbox) {
            console.error('Could not find checkbox in row');
            return;
        }
        
        const itemId = checkbox.getAttribute('data-id');
        if (!itemId || isNaN(parseInt(itemId))) {
            console.error('Invalid item ID:', itemId);
            if (ensureSwal()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Flock ID',
                    text: 'Cannot edit flock with invalid ID',
                    confirmButtonText: 'OK'
                });
            } else {
                alert('Cannot edit flock with invalid ID');
            }
            tr.remove();
            flockList.reIndex();
            flockList.update();
            return;
        }
        
        console.log('Edit clicked for ID:', itemId);
        editlist = true;
        if (editIdField) editIdField.value = itemId;
        
        const initialBirdCountCell = tr.querySelector('.initial_bird_count');
        const currentBirdCountCell = tr.querySelector('.current_bird_count');
        
        if (editInitialBirdCountField && initialBirdCountCell) {
            editInitialBirdCountField.value = initialBirdCountCell.innerText.trim();
        }
        
        if (editCurrentBirdCount && currentBirdCountCell) {
            editCurrentBirdCount.value = currentBirdCountCell.innerText.trim();
        }
        
        const editModal = document.getElementById('editFlockModal');
        if (editModal) {
            const modal = new bootstrap.Modal(editModal);
            modal.show();
        } else {
            console.error('Edit modal not found');
        }
    } catch (error) {
        console.error('Error in edit-item-btn click:', error);
        if (ensureSwal()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to populate edit modal',
                confirmButtonText: 'OK'
            });
        } else {
            alert('Failed to populate edit modal');
        }
    }
}

function clearAddFields() {
    if (addIdField) addIdField.value = '';
    if (addInitialBirdCount) addInitialBirdCount.value = '';
}

function clearEditFields() {
    if (editIdField) editIdField.value = '';
    if (editInitialBirdCountField) editInitialBirdCountField.value = '';
    if (editCurrentBirdCount) editCurrentBirdCount.value = '';
}

function deleteMultiple() {
    const ids_array = [];
    const checkboxes = document.querySelectorAll('.chk-child:checked');
    
    checkboxes.forEach(checkbox => {
        const id = checkbox.getAttribute('data-id');
        if (id && !isNaN(parseInt(id))) {
            ids_array.push(id);
        } else {
            console.warn('Skipping invalid ID:', id);
        }
    });
    
    if (ids_array.length === 0) {
        if (ensureSwal()) {
            Swal.fire({
                title: 'Please select at least one valid checkbox',
                confirmButtonClass: 'btn btn-info',
                buttonsStyling: false,
                showCloseButton: true
            });
        } else {
            alert('Please select at least one checkbox');
        }
        return;
    }
    
    const confirmAction = () => {
        if (!ensureAxios()) return;
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error('CSRF token not found');
            return;
        }
        
        Promise.all(ids_array.map(id => {
            return axios.delete(`/flocks/${id}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken.content }
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
                    }
                } else {
                    if (flockList) {
                        const item = flockList.items.find(i => {
                            const itemId = i._values.id || i.elm.querySelector('.chk-child')?.getAttribute('data-id');
                            return itemId === id;
                        });
                        if (item) {
                            flockList.remove('id', id);
                        }
                    }
                }
            });
            
            if (flockList) {
                flockList.reIndex();
                flockList.update();
            }
            
            updateFlockChart();
            updateStats(null, null);
            
            if (ensureSwal()) {
                Swal.fire({
                    title: hasErrors ? 'Partial Success' : 'Deleted!',
                    text: hasErrors ? 'Some flocks could not be deleted.' : 'Flocks deleted successfully.',
                    icon: hasErrors ? 'warning' : 'success',
                    confirmButtonClass: 'btn btn-info w-xs mt-2',
                    buttonsStyling: false
                });
            } else {
                alert(hasErrors ? 'Some flocks could not be deleted.' : 'Flocks deleted successfully!');
            }
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

document.addEventListener('DOMContentLoaded', function() {
    const addForm = document.getElementById('add-flock-form');
    if (addForm) {
        addForm.addEventListener('submit', e => {
            e.preventDefault();
            
            const errorMsg = document.getElementById('add-error-msg');
            if (errorMsg) errorMsg.classList.add('d-none');
            
            if (!addInitialBirdCount || !addInitialBirdCount.value || parseInt(addInitialBirdCount.value) < 0) {
                if (errorMsg) {
                    errorMsg.innerText = 'Please enter a valid initial bird count';
                    errorMsg.classList.remove('d-none');
                    setTimeout(() => errorMsg.classList.add('d-none'), 3000);
                }
                return;
            }
            
            if (!ensureAxios()) return;
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                return;
            }
            
            axios.post('/flocks', {
                initial_bird_count: addInitialBirdCount.value,
                current_bird_count: addInitialBirdCount.value,
                _token: csrfToken.content
            }).then(response => {
                console.log('Add response:', response.data);
                
                const newFlock = {
                    id: response.data.id.toString(),
                    initial_bird_count: response.data.initial_bird_count.toString(),
                    current_bird_count: response.data.current_bird_count.toString(),
                    created_at: formatDate(response.data.created_at)
                };
                
                if (flockList) {
                    const existingItem = flockList.items.find(i => {
                        const id = i._values.id || i.elm.querySelector('.chk-child')?.getAttribute('data-id');
                        return id === newFlock.id;
                    });
                    
                    if (existingItem) {
                        console.warn('Removing existing item with ID:', newFlock.id);
                        flockList.remove('id', newFlock.id);
                    }
                    
                    flockList.add(newFlock);
                    flockList.reIndex();
                    flockList.update();
                }
                
                updateFlockChart();
                
                const modal = bootstrap.Modal.getInstance(document.getElementById('addFlockModal'));
                if (modal) modal.hide();
                
                if (ensureSwal()) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Flock added successfully!',
                        showConfirmButton: false,
                        timer: 1500,
                        showCloseButton: true
                    });
                } else {
                    alert('Flock added successfully!');
                }
                
                clearAddFields();
            }).catch(error => {
                console.error('Error adding flock:', error);
                let message = error.response?.data?.message || 'Error adding flock';
                if (error.response?.status === 422 && error.response.data.errors) {
                    message = Object.values(error.response.data.errors).flat().join(', ');
                }
                
                if (errorMsg) {
                    errorMsg.innerText = message;
                    errorMsg.classList.remove('d-none');
                    setTimeout(() => errorMsg.classList.add('d-none'), 3000);
                }
            });
        });
    }
    
    const editForm = document.getElementById('edit-flock-form');
    if (editForm) {
        editForm.addEventListener('submit', e => {
            e.preventDefault();
            
            const errorMsg = document.getElementById('edit-error-msg');
            if (errorMsg) errorMsg.classList.add('d-none');
            
            if (!editInitialBirdCountField || !editInitialBirdCountField.value || parseInt(editInitialBirdCountField.value) < 0) {
                if (errorMsg) {
                    errorMsg.innerText = 'Please enter a valid initial bird count';
                    errorMsg.classList.remove('d-none');
                    setTimeout(() => errorMsg.classList.add('d-none'), 3000);
                }
                return;
            }
            
            if (!editCurrentBirdCount || !editCurrentBirdCount.value || parseInt(editCurrentBirdCount.value) < 0) {
                if (errorMsg) {
                    errorMsg.innerText = 'Please enter a valid current bird count';
                    errorMsg.classList.remove('d-none');
                    setTimeout(() => errorMsg.classList.add('d-none'), 3000);
                }
                return;
            }
            
            const itemId = editIdField.value;
            if (!itemId || isNaN(parseInt(itemId))) {
                console.error('Invalid item ID for edit:', itemId);
                if (errorMsg) {
                    errorMsg.innerText = 'Invalid flock ID';
                    errorMsg.classList.remove('d-none');
                    setTimeout(() => errorMsg.classList.add('d-none'), 3000);
                }
                return;
            }
            
            if (!ensureAxios()) return;
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                return;
            }
            
            axios.put(`/flocks/${itemId}`, {
                initial_bird_count: editInitialBirdCountField.value,
                current_bird_count: editCurrentBirdCount.value,
                _token: csrfToken.content
            }).then(response => {
                console.log('Update response:', response.data);
                
                if (flockList) {
                    const item = flockList.items.find(i => {
                        const id = i._values.id || i.elm.querySelector('.chk-child')?.getAttribute('data-id');
                        return id === itemId;
                    });
                    
                    if (item) {
                        item.values({
                            id: response.data.id.toString(),
                            initial_bird_count: response.data.initial_bird_count.toString(),
                            current_bird_count: response.data.current_bird_count.toString(),
                            created_at: formatDate(response.data.created_at)
                        });
                        
                        const checkbox = item.elm.querySelector('.chk-child');
                        const link = item.elm.querySelector('a');
                        if (checkbox) checkbox.setAttribute('data-id', response.data.id);
                        if (link) link.href = `/flocks/${response.data.id}`;
                    } else {
                        console.warn('List.js item not found, updating DOM manually');
                        const tr = document.querySelector(`[data-id="${itemId}"]`)?.closest('tr');
                        if (tr) {
                            const initialCell = tr.querySelector('.initial_bird_count');
                            const currentCell = tr.querySelector('.current_bird_count');
                            const createdCell = tr.querySelector('.created_at');
                            const checkbox = tr.querySelector('.chk-child');
                            const link = tr.querySelector('a');
                            
                            if (initialCell) initialCell.innerText = response.data.initial_bird_count;
                            if (currentCell) currentCell.innerText = response.data.current_bird_count;
                            if (createdCell) createdCell.innerText = formatDate(response.data.created_at);
                            if (checkbox) checkbox.setAttribute('data-id', response.data.id);
                            if (link) link.href = `/flocks/${response.data.id}`;
                        }
                    }
                    
                    flockList.reIndex();
                    flockList.update();
                }
                
                updateFlockChart();
                updateStats(null, null);
                
                const modal = bootstrap.Modal.getInstance(document.getElementById('editFlockModal'));
                if (modal) modal.hide();
                
                if (ensureSwal()) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Flock updated successfully!',
                        showConfirmButton: false,
                        timer: 2000,
                        showCloseButton: true
                    });
                } else {
                    alert('Flock updated successfully!');
                }
                
                clearEditFields();
            }).catch(error => {
                console.error('Error updating flock:', error);
                let message = error.response?.data?.message || 'Error updating flock';
                if (error.response?.status === 404) {
                    message = `Flock ID ${itemId} not found`;
                    console.warn('Removing stale row for ID:', itemId);
                    const tr = document.querySelector(`[data-id="${itemId}"]`)?.closest('tr');
                    if (tr) tr.remove();
                    flockList.reIndex();
                    flockList.update();
                }
                if (errorMsg) {
                    errorMsg.innerText = message;
                    errorMsg.classList.remove('d-none');
                    setTimeout(() => errorMsg.classList.add('d-none'), 3000);
                }
            });
        });
    }
    
    const addModal = document.getElementById('addFlockModal');
    if (addModal) {
        addModal.addEventListener('show.bs.modal', () => {
            console.log('Opening addFlockModal...');
            const modalLabel = document.getElementById('addModalLabel');
            const addBtn = document.getElementById('add-btn');
            if (modalLabel) modalLabel.innerText = 'Add Flock';
            if (addBtn) addBtn.innerText = 'Add Flock';
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
            if (modalLabel) modalLabel.innerText = 'Edit Flock';
            if (updateBtn) updateBtn.innerText = 'Update';
        });
        editModal.addEventListener('hidden.bs.modal', () => {
            console.log('editFlockModal closed, clearing fields...');
            clearEditFields();
        });
    }
});