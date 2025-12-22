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

function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
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
    const addStatus = document.getElementById('status');
    if (addIdField) addIdField.value = '';
    if (addInitialBirdCount) addInitialBirdCount.value = '';
    if (addStatus) addStatus.value = 'active';
}

function clearEditFields() {
    const editIdField = document.getElementById('edit-id-field');
    const editInitialBirdCountField = document.getElementById('edit-initial_bird_count');
    const editCurrentBirdCount = document.getElementById('edit-current_bird_count');
    const editStatus = document.getElementById('edit-status');
    if (editIdField) editIdField.value = '';
    if (editInitialBirdCountField) editInitialBirdCountField.value = '';
    if (editCurrentBirdCount) editCurrentBirdCount.value = '';
    if (editStatus) editStatus.value = 'active';
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
            valueNames: ['id', 'initial_bird_count', 'current_bird_count', 'status', 'created_at'],
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

// Filter data based on search, bird count, and status
function filterData(url = null) {
    const search = document.getElementById('searchFlock')?.value || '';
    const birdCountFilter = document.getElementById('birdCountFilter')?.value || 'all';
    const statusFilter = document.getElementById('statusFilter')?.value || 'all';
    const fetchUrl = url || `/flocks?search=${encodeURIComponent(search)}&bird_count_filter=${encodeURIComponent(birdCountFilter)}&status_filter=${encodeURIComponent(statusFilter)}`;

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

// Handle edit button click - UPDATED FOR STATUS
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
        const editStatus = document.getElementById('edit-status');

        if (editIdField) editIdField.value = itemId;
        const initialBirdCountCell = tr.querySelector('.initial_bird_count');
        const currentBirdCountCell = tr.querySelector('.current_bird_count');
        const statusCell = tr.querySelector('.status');

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

        if (editStatus && statusCell) {
            const statusBadge = statusCell.querySelector('.badge');
            if (statusBadge) {
                const statusClass = statusBadge.className;
                let statusValue = 'active';
                if (statusClass.includes('badge-status-inactive')) statusValue = 'inactive';
                else if (statusClass.includes('badge-status-sold')) statusValue = 'sold';
                else if (statusClass.includes('badge-status-ended')) statusValue = 'ended';
                editStatus.value = statusValue;
            }
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

// Handle edit flock form submission - UPDATED FOR STATUS
const editForm = document.getElementById('edit-flock-form');
if (editForm) {
    editForm.addEventListener('submit', e => {
        e.preventDefault();
        console.log('Edit form submitted');

        const errorMsg = document.getElementById('edit-error-msg');
        if (errorMsg) errorMsg.classList.add('d-none');

        const editInitialBirdCountField = document.getElementById('edit-initial_bird_count');
        const editCurrentBirdCount = document.getElementById('edit-current_bird_count');
        const editStatus = document.getElementById('edit-status');
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
            status: editStatus.value,
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
                        status: response.data.status,
                        status_formatted: capitalizeFirst(response.data.status),
                        created_at: formatDate(response.data.created_at)
                    });

                    const checkbox = item.elm.querySelector('.chk-child.id');
                    const link = item.elm.querySelector('a');
                    const statusCell = item.elm.querySelector('.status');
                    if (checkbox) checkbox.setAttribute('data-id', response.data.id);
                    if (link) link.href = `/flocks/${response.data.id}/week-entries`;
                    if (statusCell) {
                        const badge = statusCell.querySelector('.badge');
                        if (badge) {
                            badge.className = `badge badge-status-${response.data.status}`;
                            badge.textContent = capitalizeFirst(response.data.status);
                        }
                    }
                } else {
                    console.warn('List.js item not found, updating DOM manually');
                    const tr = document.querySelector(`[data-id="${itemId}"]`)?.closest('tr');
                    if (tr) {
                        const initialCell = tr.querySelector('.initial_bird_count');
                        const currentCell = tr.querySelector('.current_bird_count');
                        const statusCell = tr.querySelector('.status');
                        const createdCell = tr.querySelector('.created_at');
                        const checkbox = tr.querySelector('.chk-child.id');
                        const link = tr.querySelector('a');

                        if (initialCell) initialCell.textContent = response.data.initial_bird_count;
                        if (currentCell) currentCell.textContent = response.data.current_bird_count;
                        if (statusCell) {
                            const badge = statusCell.querySelector('.badge');
                            if (badge) {
                                badge.className = `badge badge-status-${response.data.status}`;
                                badge.textContent = capitalizeFirst(response.data.status);
                            }
                        }
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
                    const statusCell = tr.querySelector('.status');
                    const createdCell = tr.querySelector('.created_at');
                    const checkbox = tr.querySelector('.chk-child.id');
                    const link = tr.querySelector('a');

                    if (initialCell) initialCell.textContent = response.data.initial_bird_count;
                    if (currentCell) currentCell.textContent = response.data.current_bird_count;
                    if (statusCell) {
                        const badge = statusCell.querySelector('.badge');
                        if (badge) {
                            badge.className = `badge badge-status-${response.data.status}`;
                            badge.textContent = capitalizeFirst(response.data.status);
                        }
                    }
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

// Handle add flock form submission - UPDATED FOR STATUS
const addForm = document.getElementById('add-flock-form');
if (addForm) {
    addForm.addEventListener('submit', e => {
        e.preventDefault();
        console.log('Add form submitted');

        const errorMsg = document.getElementById('add-error-msg');
        const addInitialBirdCount = document.getElementById('initial_bird_count');
        const addStatus = document.getElementById('status');
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
            status: addStatus.value,
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
                status: response.data.status,
                status_formatted: capitalizeFirst(response.data.status),
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
                        .replace('{status}', newFlock.status)
                        .replace('{status_formatted}', newFlock.status_formatted)
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

// Main initialization
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing...');
    
    // Initialize List.js
    if (!initializeList()) {
        console.error('Failed to initialize List.js');
        console.warn('Attaching event listeners manually as fallback');
        refreshCallbacks();
        ischeckboxcheck();
        updateFlockChart();
    }

    // ... (rest of the initialization code remains the same)
    
    // Initial setup
    refreshCallbacks();
    ischeckboxcheck();
    updateFlockChart();
    updateStats(null, null);
});