const perPage = 5;
let editlist = false;
let flockList = null;
let flockChart = null;
let birdCountFilterVal = null;

// Pass bird_count_ranges from PHP to JavaScript
const birdCountRanges = JSON.parse(document.querySelector('meta[name="bird-count-ranges"]')?.content || '{}');

// DOM Elements
const checkAll = document.getElementById("checkAll");
const addIdField = document.getElementById("add-id-field");
const addInitialBirdCount = document.getElementById("initial_bird_count");
const editIdField = document.getElementById("edit-id-field");
const editInitialBirdCountField = document.getElementById("edit-initial_bird_count");
const editCurrentBirdCount = document.getElementById("edit-current_bird_count");

// Initialize Chart
function initializeChart() {
    const ctx = document.getElementById("flockChart");
    if (!ctx) {
        console.error("Chart canvas not found");
        return false;
    }

    // Destroy existing chart instance if it exists
    if (flockChart) {
        console.log("Destroying existing chart instance");
        flockChart.destroy();
        flockChart = null;
    }

    try {
        flockChart = new Chart(ctx.getContext("2d"), {
            type: "bar",
            data: {
                labels: ["0-100", "101-200", "201-500", "501+"],
                datasets: [{
                    label: "Flock Count",
                    data: [
                        birdCountRanges['0-100'] || 0,
                        birdCountRanges['101-200'] || 0,
                        birdCountRanges['201-500'] || 0,
                        birdCountRanges['501+'] || 0
                    ],
                    backgroundColor: ["#4e79a7", "#f28e2c", "#e15759", "#76b7b2"],
                    borderColor: ["#4e79a7", "#f28e2c", "#e15759", "#76b7b2"],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: "Number of Flocks" }
                    },
                    x: {
                        title: { display: true, text: "Initial Bird Count" }
                    }
                },
                plugins: {
                    legend: { display: true }
                }
            }
        });
        console.log("Chart initialized");
        return true;
    } catch (error) {
        console.error("Error initializing chart:", error);
        return false;
    }
}

// Update Chart
function updateChart() {
    if (!flockChart) return;

    axios.get('/flocks')
        .then(response => {
            const ranges = response.data.bird_count_ranges || birdCountRanges;
            flockChart.data.datasets[0].data = [
                ranges['0-100'] || 0,
                ranges['101-200'] || 0,
                ranges['201-500'] || 0,
                ranges['501+'] || 0
            ];
            flockChart.update();
            console.log("Chart updated with data:", ranges);
        })
        .catch(error => {
            console.error("Error updating chart:", error);
        });
}

// Format Date
function formatDate(dateStr) {
    try {
        const date = new Date(dateStr);
        return date.toISOString().split('T')[0];
    } catch (error) {
        console.error("Error formatting date:", error);
        return dateStr;
    }
}

// Initialize List.js - FIXED
function initializeList() {
    try {
        const options = {
            valueNames: [
                { data: ['id'] },
                'initial_bird_count', 
                'current_bird_count', 
                'created_at'
            ],
            page: perPage,
            pagination: false,
            item: function(values) {
                return `<tr>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input chk-child" type="checkbox" name="chk_child" value="${values.id}" data-id="${values.id}">
                            <label class="form-check-label"></label>
                        </div>
                    </td>
                    <td class="initial_bird_count">${values.initial_bird_count}</td>
                    <td class="current_bird_count">${values.current_bird_count}</td>
                    <td class="created_at">${values.created_at}</td>
                    <td>
                        <div class="hstack gap-2">
                            <a href="/week-entries/${values.id}" class="btn btn-subtle-primary btn-icon btn-sm" title="View flock"><i class="ph-eye"></i></a>
                            <button type="button" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" data-flock-id="${values.id}" title="Edit flock"><i class="ph-pencil"></i></button>
                            <button type="button" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" data-flock-id="${values.id}" title="Delete flock"><i class="ph-trash"></i></button>
                        </div>
                    </td>
                </tr>`;
            }
        };

        flockList = new List('flockList', options);
        console.log("List.js initialized with", flockList.items.length, "items");
        return true;
    } catch (error) {
        console.error("Error initializing List.js:", error);
        return false;
    }
}

// Ensure Axios
function ensureAxios() {
    if (typeof axios === 'undefined') {
        console.error("Axios is not defined");
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: "error",
                title: "Configuration Error",
                text: "Axios library is missing",
                confirmButtonText: "OK"
            });
        }
        return false;
    }
    return true;
}

// Ensure SweetAlert2
function ensureSwal() {
    return typeof Swal !== 'undefined';
}

// Checkbox Handlers
function ischeckboxcheck() {
    const checkboxes = document.querySelectorAll(".chk-child");
    checkboxes.forEach(checkbox => {
        checkbox.removeEventListener("change", handleCheckboxChange);
        checkbox.addEventListener("change", handleCheckboxChange);
    });
}

function handleCheckboxChange(e) {
    const row = e.target.closest("tr");
    if (row) {
        row.classList.toggle("table-active", e.target.checked);
    }

    const checkedCount = document.querySelectorAll(".chk-child:checked").length;
    const removeActions = document.getElementById("remove-actions");
    if (removeActions) {
        removeActions.classList.toggle("d-none", checkedCount === 0);
    }

    if (checkAll) {
        const allCheckboxes = document.querySelectorAll(".chk-child");
        checkAll.checked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
    }
}

// Refresh Callbacks - IMPROVED
function refreshCallbacks() {
    console.log("Refreshing callbacks...");
    
    // Use setTimeout to ensure DOM is updated
    setTimeout(() => {
        const removeButtons = document.querySelectorAll(".remove-item-btn");
        const editButtons = document.querySelectorAll(".edit-item-btn");

        console.log("Found", editButtons.length, "edit buttons and", removeButtons.length, "remove buttons");

        removeButtons.forEach(btn => {
            btn.removeEventListener("click", handleRemoveClick);
            btn.addEventListener("click", handleRemoveClick);
        });

        editButtons.forEach(btn => {
            btn.removeEventListener("click", handleEditClick);
            btn.addEventListener("click", handleEditClick);
        });

        // Refresh checkbox handlers
        ischeckboxcheck();
    }, 100);
}

// Handle Edit Click
function handleEditClick(e) {
    e.preventDefault();
    console.log("Edit button clicked");

    try {
        const button = e.currentTarget;
        const itemId = button.getAttribute("data-flock-id");
        if (!itemId || isNaN(parseInt(itemId))) {
            console.error("Invalid flock ID:", itemId);
            if (ensureSwal()) {
                Swal.fire({
                    icon: "error",
                    title: "Invalid Flock ID",
                    text: "Cannot edit flock with invalid ID",
                    confirmButtonText: "OK"
                });
            } else {
                alert("Cannot edit flock with invalid ID");
            }
            return;
        }

        const tr = button.closest("tr");
        if (!tr) {
            console.error("Table row not found");
            return;
        }

        console.log("Editing flock ID:", itemId);

        editlist = true;
        if (editIdField) editIdField.value = itemId;

        const initialBirdCountCell = tr.querySelector(".initial_bird_count");
        const currentBirdCountCell = tr.querySelector(".current_bird_count");

        if (editInitialBirdCountField && initialBirdCountCell) {
            editInitialBirdCountField.value = initialBirdCountCell.textContent.trim();
        }

        if (editCurrentBirdCount && currentBirdCountCell) {
            editCurrentBirdCount.value = currentBirdCountCell.textContent.trim();
        }

        const editModal = document.getElementById("editFlockModal");
        if (editModal) {
            const modal = new bootstrap.Modal(editModal);
            modal.show();
            console.log("Edit modal opened for ID:", itemId);
        }
    } catch (error) {
        console.error("Error in handleEditClick:", error);
        if (ensureSwal()) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Failed to open edit modal",
                confirmButtonText: "OK"
            });
        } else {
            alert("Failed to open edit modal");
        }
    }
}

// Handle Remove Click
function handleRemoveClick(e) {
    e.preventDefault();
    console.log("Delete button clicked");

    try {
        const button = e.currentTarget;
        const itemId = button.getAttribute("data-flock-id");
        if (!itemId || isNaN(parseInt(itemId))) {
            console.error("Invalid flock ID:", itemId);
            if (ensureSwal()) {
                Swal.fire({
                    icon: "error",
                    title: "Invalid Flock ID",
                    text: "Cannot delete flock with invalid ID",
                    confirmButtonText: "OK"
                });
            } else {
                alert("Cannot delete flock with invalid ID");
            }
            return;
        }

        console.log("Deleting flock ID:", itemId);

        const deleteModal = document.getElementById("deleteRecordModal");
        if (!deleteModal) {
            console.error("Delete modal element not found");
            return;
        }

        const modal = new bootstrap.Modal(deleteModal);
        modal.show();
        console.log("Delete modal opened for ID:", itemId);

        const deleteButton = document.getElementById("delete-record");
        if (!deleteButton) {
            console.error("Delete button not found");
            modal.hide();
            return;
        }

        const deleteHandler = () => {
            if (!ensureAxios()) {
                modal.hide();
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error("CSRF token not found");
                modal.hide();
                return;
            }

            axios.delete(`/flocks/${itemId}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken.content }
            }).then(() => {
                console.log("Deleted flock ID:", itemId);

                // Remove from List.js
                if (flockList) {
                    const itemsToRemove = flockList.items.filter(item => 
                        item._values.id === itemId || item.values().id === itemId
                    );
                    
                    itemsToRemove.forEach(item => {
                        flockList.remove('id', itemId);
                    });
                    
                    flockList.update();
                    refreshCallbacks();
                }

                updateChart();
                modal.hide();

                if (ensureSwal()) {
                    Swal.fire({
                        icon: "success",
                        title: "Deleted successfully!",
                        showConfirmButton: false,
                        timer: 2000,
                        showCloseButton: true
                    });
                } else {
                    alert("Deleted successfully!");
                }
            }).catch(error => {
                console.error("Error deleting flock:", error);
                modal.hide();

                let message = error.response?.data?.message || "An error occurred";
                if (error.response?.status === 404) {
                    message = `Flock ID ${itemId} not found`;
                    // Still remove from UI if 404
                    if (flockList) {
                        flockList.remove('id', itemId);
                        flockList.update();
                        refreshCallbacks();
                    }
                }

                if (ensureSwal()) {
                    Swal.fire({
                        icon: "error",
                        title: "Error Deleting Flock",
                        text: message,
                        confirmButtonText: "OK"
                    });
                } else {
                    alert("Error deleting flock: " + message);
                }
            });
        };

        deleteButton.removeEventListener("click", deleteHandler);
        deleteButton.addEventListener("click", deleteHandler, { once: true });
    } catch (error) {
        console.error("Error in handleRemoveClick:", error);
    }
}

// Clear Fields
function clearAddFields() {
    if (addIdField) addIdField.value = "";
    if (addInitialBirdCount) addInitialBirdCount.value = "";
}

function clearEditFields() {
    if (editIdField) editIdField.value = "";
    if (editInitialBirdCountField) editInitialBirdCountField.value = "";
    if (editCurrentBirdCount) editCurrentBirdCount.value = "";
}

// Delete Multiple - IMPROVED
function deleteMultiple() {
    const ids_array = [];
    const checkboxes = document.querySelectorAll(".chk-child:checked");

    checkboxes.forEach(checkbox => {
        const id = checkbox.getAttribute("data-id");
        if (id && !isNaN(parseInt(id))) {
            ids_array.push(id);
        }
    });

    if (ids_array.length === 0) {
        if (ensureSwal()) {
            Swal.fire({
                title: "Please select at least one valid checkbox",
                confirmButtonClass: "btn btn-info",
                buttonsStyling: false,
                showCloseButton: true
            });
        } else {
            alert("Please select at least one checkbox");
        }
        return;
    }

    if (ensureSwal()) {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn btn-primary w-xs me-2 mt-2",
            cancelButtonClass: "btn btn-danger w-xs mt-2",
            confirmButtonText: "Yes, delete it!",
            buttonsStyling: false,
            showCloseButton: true
        }).then(result => {
            if (result.isConfirmed) {
                performDeleteMultiple(ids_array);
            }
        });
    } else {
        if (confirm("Are you sure you want to delete the selected flocks?")) {
            performDeleteMultiple(ids_array);
        }
    }
}

function performDeleteMultiple(ids_array) {
    if (!ensureAxios()) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error("CSRF token not found");
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
            }
            
            // Remove from List.js regardless of success/error
            if (flockList) {
                flockList.remove('id', id);
            }
        });

        if (flockList) {
            flockList.update();
            refreshCallbacks();
        }

        updateChart();

        if (ensureSwal()) {
            Swal.fire({
                title: hasErrors ? "Partial Success" : "Deleted!",
                text: hasErrors ? "Some flocks could not be deleted." : "Flocks deleted successfully.",
                icon: hasErrors ? "warning" : "success",
                confirmButtonClass: "btn btn-info w-xs mt-2",
                buttonsStyling: false
            });
        } else {
            alert(hasErrors ? "Some flocks could not be deleted." : "Flocks deleted successfully!");
        }
    });
}

// Filter Data
function filterData() {
    const searchInput = document.querySelector("#searchFlock");
    const birdCountSelect = document.getElementById("birdCountFilter");

    if (!searchInput || !birdCountSelect || !flockList) return;

    const searchValue = searchInput.value.toLowerCase();
    const selectedBirdCount = birdCountFilterVal ? birdCountFilterVal.getValue(true) : birdCountSelect.value;

    console.log("Filtering with:", { search: searchValue, birdCount: selectedBirdCount });

    flockList.filter(item => {
        const values = item.values();
        const birdCount = parseInt(values.initial_bird_count) || 0;

        const birdCountMatch = selectedBirdCount === "all" ||
            (selectedBirdCount === "0-100" && birdCount <= 100) ||
            (selectedBirdCount === "101-200" && birdCount > 100 && birdCount <= 200) ||
            (selectedBirdCount === "201-500" && birdCount > 200 && birdCount <= 500) ||
            (selectedBirdCount === "501+" && birdCount > 500);

        let searchMatch = true;
        if (searchValue) {
            searchMatch = values.initial_bird_count.toLowerCase().includes(searchValue) ||
                values.current_bird_count.toLowerCase().includes(searchValue) ||
                values.created_at.toLowerCase().includes(searchValue);
        }

        return birdCountMatch && searchMatch;
    });
}

// DOM Loaded
document.addEventListener("DOMContentLoaded", () => {
    console.log("DOM loaded, initializing...");

    // Initialize Chart
    initializeChart();

    // Initialize List.js
    if (!initializeList()) {
        console.error("Failed to initialize List.js");
        return;
    }

    // Initialize Choices.js
    try {
        if (typeof Choices !== 'undefined') {
            const birdCountSelect = document.getElementById("birdCountFilter");
            if (birdCountSelect) {
                birdCountFilterVal = new Choices(birdCountSelect, {
                    searchEnabled: true,
                    removeItemButton: true
                });
                console.log("Choices.js initialized");
            }
        } else {
            console.warn("Choices.js not available, using default select element");
            const birdCountSelect = document.getElementById("birdCountFilter");
            if (birdCountSelect) {
                birdCountSelect.addEventListener("change", () => filterData());
            }
        }
    } catch (error) {
        console.error("Error initializing Choices.js:", error);
    }

    // Checkbox All
    if (checkAll) {
        checkAll.addEventListener("click", function() {
            const checkboxes = document.querySelectorAll(".chk-child");
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                checkbox.closest("tr").classList.toggle("table-active", this.checked);
            });
            const removeActions = document.getElementById("remove-actions");
            if (removeActions) {
                removeActions.classList.toggle("d-none", !this.checked);
            }
        });
    }

    refreshCallbacks();
    ischeckboxcheck();
    updateChart();

    const searchInput = document.getElementById("searchFlock");
    if (searchInput) {
        searchInput.addEventListener("input", function() {
            const searchValue = this.value.toLowerCase();
            if (flockList) {
                flockList.search(searchValue, ['initial_bird_count', 'current_bird_count', 'created_at']);
            }
        });
    }

    // Add Form - FIXED
    const addForm = document.getElementById("add-flock-form");
    if (addForm) {
        addForm.addEventListener("submit", e => {
            e.preventDefault();
            const errorMsg = document.getElementById("add-error-msg");
            if (errorMsg) errorMsg.classList.add("d-none");

            if (!addInitialBirdCount || !addInitialBirdCount.value || parseInt(addInitialBirdCount.value) < 0) {
                if (errorMsg) {
                    errorMsg.textContent = "Please enter a valid initial bird count";
                    errorMsg.classList.remove("d-none");
                    setTimeout(() => errorMsg.classList.add("d-none"), 3000);
                }
                return;
            }

            if (!ensureAxios()) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error("CSRF token not found");
                return;
            }

            axios.post('/flocks', {
                initial_bird_count: addInitialBirdCount.value,
                current_bird_count: addInitialBirdCount.value,
                _token: csrfToken.content
            }).then(response => {
                console.log("Add response:", response.data);

                const newFlock = {
                    id: response.data.id.toString(),
                    initial_bird_count: response.data.initial_bird_count.toString(),
                    current_bird_count: response.data.current_bird_count.toString(),
                    created_at: formatDate(response.data.created_at)
                };

                if (flockList) {
                    // Check for duplicates and remove them
                    const existingItems = flockList.items.filter(item => 
                        item._values.id === newFlock.id || item.values().id === newFlock.id
                    );
                    
                    existingItems.forEach(item => {
                        flockList.remove('id', newFlock.id);
                    });

                    // Add new item
                    flockList.add(newFlock);
                    flockList.update();
                    refreshCallbacks();
                }

                updateChart();

                const modal = bootstrap.Modal.getInstance(document.getElementById("addFlockModal"));
                if (modal) modal.hide();

                if (ensureSwal()) {
                    Swal.fire({
                        icon: "success",
                        title: "Flock added successfully!",
                        showConfirmButton: false,
                        timer: 1500,
                        showCloseButton: true
                    });
                } else {
                    alert("Flock added successfully!");
                }

                clearAddFields();
            }).catch(error => {
                console.error("Error adding flock:", error);
                let message = error.response?.data?.message || "Error adding flock";
                if (error.response?.status === 422 && error.response.data.errors) {
                    message = Object.values(error.response.data.errors).flat().join(", ");
                }

                if (errorMsg) {
                    errorMsg.textContent = message;
                    errorMsg.classList.remove("d-none");
                    setTimeout(() => errorMsg.classList.add("d-none"), 3000);
                }
            });
        });
    }

    // Edit Form - FIXED
    const editForm = document.getElementById("edit-flock-form");
    if (editForm) {
        editForm.addEventListener("submit", e => {
            e.preventDefault();

            const errorMsg = document.getElementById("edit-error-msg");
            if (errorMsg) errorMsg.classList.add("d-none");

            if (!editInitialBirdCountField || !editInitialBirdCountField.value || parseInt(editInitialBirdCountField.value) < 0) {
                if (errorMsg) {
                    errorMsg.textContent = "Please enter a valid initial bird count";
                    errorMsg.classList.remove("d-none");
                    setTimeout(() => errorMsg.classList.add("d-none"), 3000);
                }
                return;
            }

            if (!editCurrentBirdCount || !editCurrentBirdCount.value || parseInt(editCurrentBirdCount.value) < 0) {
                if (errorMsg) {
                    errorMsg.textContent = "Please enter a valid current bird count";
                    errorMsg.classList.remove("d-none");
                    setTimeout(() => errorMsg.classList.add("d-none"), 3000);
                }
                return;
            }

            const itemId = editIdField.value;
            if (!itemId || isNaN(parseInt(itemId))) {
                console.error("Invalid item ID for edit:", itemId);
                if (errorMsg) {
                    errorMsg.textContent = "Invalid item ID";
                    errorMsg.classList.remove("d-none");
                    setTimeout(() => errorMsg.classList.add("d-none"), 3000);
                }
                return;
            }

            if (!ensureAxios()) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error("CSRF token not found");
                return;
            }

            axios.put(`/flocks/${itemId}`, {
                initial_bird_count: editInitialBirdCountField.value,
                current_bird_count: editCurrentBirdCount.value,
                _token: csrfToken.content
            }).then(response => {
                console.log("Update response:", response.data);

                if (flockList) {
                    // Find and update the item
                    const item = flockList.items.find(i => 
                        i._values.id === itemId || i.values().id === itemId
                    );
                    
                    if (item) {
                        // Update the item values
                        item.values({
                            id: response.data.id.toString(),
                            initial_bird_count: response.data.initial_bird_count.toString(),
                            current_bird_count: response.data.current_bird_count.toString(),
                            created_at: formatDate(response.data.created_at)
                        });
                    }

                    flockList.update();
                    refreshCallbacks();
                }

                updateChart();

                const modal = bootstrap.Modal.getInstance(document.getElementById("editFlockModal"));
                if (modal) modal.hide();

                if (ensureSwal()) {
                    Swal.fire({
                        icon: "success",
                        title: "Flock updated successfully!",
                        showConfirmButton: false,
                        timer: 2000,
                        showCloseButton: true
                    });
                } else {
                    alert("Flock updated successfully!");
                }

                clearEditFields();
            }).catch(error => {
                console.error("Error updating flock:", error);
                let message = error.response?.data?.message || "Error updating flock";
                if (error.response?.status === 404) {
                    message = `Flock ID ${itemId} not found`;
                    // Remove the item if it doesn't exist
                    if (flockList) {
                        flockList.remove('id', itemId);
                        flockList.update();
                        refreshCallbacks();
                    }
                }
                if (errorMsg) {
                    errorMsg.textContent = message;
                    errorMsg.classList.remove("d-none");
                    setTimeout(() => errorMsg.classList.add("d-none"), 3000);
                }
            });
        });
    }

    // Modal Event Listeners
    const addModal = document.getElementById("addFlockModal");
    if (addModal) {
        addModal.addEventListener("show.bs.modal", () => {
            console.log("Opening addFlockModal...");
            clearAddFields();
        });
        addModal.addEventListener("hidden.bs.modal", () => {
            console.log("addFlockModal closed, clearing fields...");
            clearAddFields();
        });
    }

    const editModal = document.getElementById("editFlockModal");
    if (editModal) {
        editModal.addEventListener("show.bs.modal", () => {
            console.log("Opening editFlockModal...");
        });
        editModal.addEventListener("hidden.bs.modal", () => {
            console.log("editFlockModal closed, clearing fields...");
            clearEditFields();
        });
    }
});