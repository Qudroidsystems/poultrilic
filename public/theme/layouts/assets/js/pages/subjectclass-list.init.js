console.log("subjectclass.init.js is loaded and executing!");

// Verify dependencies
try {
    if (typeof axios === 'undefined') throw new Error("Axios is not loaded");
    if (typeof Swal === 'undefined') throw new Error("SweetAlert2 is not loaded");
    if (typeof bootstrap === 'undefined') throw new Error("Bootstrap is not loaded");
    if (typeof List === 'undefined') throw new Error("List.js is not loaded");
    console.log("All dependencies loaded successfully");
} catch (error) {
    console.error("Dependency check failed:", error);
}

// Set Axios CSRF token globally
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
if (!csrfToken) console.warn("CSRF token not found");

// Debounce function for search input
function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Check all checkbox
const checkAll = document.getElementById("checkAll");
if (checkAll) {
    checkAll.addEventListener("click", function () {
        console.log("CheckAll clicked");
        const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
        checkboxes.forEach((checkbox) => {
            checkbox.checked = this.checked;
            const row = checkbox.closest("tr");
            row.classList.toggle("table-active", this.checked);
        });
        const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
        const removeActions = document.getElementById("remove-actions");
        if (removeActions) {
            removeActions.classList.toggle("d-none", checkedCount === 0);
        }
    });
}

// Form fields
const addIdField = document.getElementById("add-id-field");
const addSchoolClassIdField = document.getElementById("schoolclassid");
const editIdField = document.getElementById("edit-id-field");
const editSchoolClassIdField = document.getElementById("edit-schoolclassid");

// Checkbox handling
function initializeCheckboxes() {
    const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
    checkboxes.forEach((checkbox) => {
        checkbox.removeEventListener("change", handleCheckboxChange);
        checkbox.addEventListener("change", handleCheckboxChange);
    });
}

function handleCheckboxChange(e) {
    console.log("Checkbox changed:", e.target.checked);
    const row = e.target.closest("tr");
    row.classList.toggle("table-active", e.target.checked);
    const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
    const removeActions = document.getElementById("remove-actions");
    if (removeActions) {
        removeActions.classList.toggle("d-none", checkedCount === 0);
    }
    const allCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]');
    if (checkAll) {
        checkAll.checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCount;
    }
}

// Explicit event listener for Create Subject Class button
const createButton = document.querySelector('.add-btn');
if (createButton) {
    createButton.addEventListener('click', function (e) {
        e.preventDefault();
        console.log("Create Subject Class button clicked");
        try {
            const modal = new bootstrap.Modal(document.getElementById("addSubjectClassModal"));
            modal.show();
            console.log("Add modal opened");
        } catch (error) {
            console.error("Error opening add modal:", error);
            Swal.fire({
                position: "center",
                icon: "error",
                title: "Error opening modal",
                text: "Please ensure Bootstrap is loaded and try again.",
                showConfirmButton: true
            });
        }
    });
}

// Event delegation for edit, remove, and pagination buttons
document.addEventListener('click', function (e) {
    const editBtn = e.target.closest('.edit-item-btn');
    const removeBtn = e.target.closest('.remove-item-btn');
    const paginationLink = e.target.closest('.pagination-prev, .pagination-next, .pagination .page-link');
    
    if (editBtn) {
        handleEditClick(e, editBtn);
    } else if (removeBtn) {
        handleRemoveClick(e, removeBtn);
    } else if (paginationLink) {
        e.preventDefault();
        const url = paginationLink.getAttribute('href') || paginationLink.getAttribute('data-url');
        if (url && url !== '#') fetchPage(url);
    }
});

// FIXED: Simplified page refresh function
function refreshTable() {
    console.log("Refreshing table...");
    // Simply reload the current page to ensure data consistency
    window.location.reload();
}

// Alternative: More reliable fetchPage function
function fetchPage(url) {
    if (!url) {
        console.warn("No URL provided for fetchPage, refreshing current page");
        refreshTable();
        return;
    }
    
    console.log("Fetching page:", url);
    
    // Show loading indicator
    const tableContainer = document.querySelector('.card .card-body');
    if (tableContainer) {
        const loadingHtml = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading data...</p>
            </div>
        `;
        tableContainer.innerHTML = loadingHtml;
    }
    
    axios.get(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        params: { _t: new Date().getTime() }
    }).then(function (response) {
        console.log("Fetch page response received");
        
        if (!response.data.html) {
            console.error("No HTML content in response, refreshing page");
            refreshTable();
            return;
        }

        try {
            // Create a temporary container to parse the response HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = response.data.html;
            
            // Extract only the table content and pagination from the response
            const newTableContainer = tempDiv.querySelector('.card .card-body');
            const newPagination = tempDiv.querySelector('#pagination-element');
            
            if (newTableContainer) {
                const currentTableContainer = document.querySelector('.card .card-body');
                if (currentTableContainer) {
                    currentTableContainer.innerHTML = newTableContainer.innerHTML;
                }
            }
            
            if (newPagination) {
                const currentPagination = document.querySelector('#pagination-element');
                if (currentPagination) {
                    currentPagination.innerHTML = newPagination.innerHTML;
                }
            }

            // Reinitialize components
            initializeCheckboxes();
            initializeListJS();
            
        } catch (error) {
            console.error("Error processing response, refreshing page:", error);
            refreshTable();
        }
        
    }).catch(function (error) {
        console.error("Error fetching page:", error.response || error);
        
        // Fallback to page refresh on error
        console.log("Falling back to page refresh");
        refreshTable();
    });
}

// Delete single subject class
function handleRemoveClick(e, button) {
    e.preventDefault();
    console.log("Remove button clicked");
    
    const itemId = button.closest("tr").querySelector(".id")?.getAttribute("data-id");
    const deleteUrl = button.closest("tr").getAttribute("data-url");
    
    if (!itemId || !deleteUrl) {
        console.error("Item ID or delete URL not found");
        return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById("deleteRecordModal"));
    modal.show();
    console.log("Delete modal opened");

    const deleteButton = document.getElementById("delete-record");
    if (deleteButton) {
        // Remove any existing event listeners to prevent duplicates
        deleteButton.onclick = null;
        
        deleteButton.onclick = function () {
            console.log("Deleting subject class:", itemId);
            
            axios.delete(deleteUrl)
                .then(function (response) {
                    console.log("Delete success:", response.data);
                    
                    Swal.fire({
                        position: "center",
                        icon: "success",
                        title: response.data.message || "Subject Class deleted successfully!",
                        showConfirmButton: false,
                        timer: 2000,
                        showCloseButton: true
                    });
                    
                    modal.hide();
                    // Use page refresh for reliability
                    setTimeout(() => refreshTable(), 500);
                })
                .catch(function (error) {
                    console.error("Delete error:", error.response?.data || error);
                    
                    Swal.fire({
                        position: "center",
                        icon: "error",
                        title: "Error deleting subject class",
                        text: error.response?.data?.message || "An error occurred",
                        showConfirmButton: true
                    });
                    modal.hide();
                });
        };
    }
}

// Helper function to get current page URL
function getCurrentPageUrl() {
    const activePageLink = document.querySelector('.pagination .page-item.active .page-link');
    if (activePageLink) {
        return activePageLink.getAttribute('href') || activePageLink.getAttribute('data-url') || '/subjectclass';
    }
    return '/subjectclass';
}

// Edit subject class
function handleEditClick(e, button) {
    e.preventDefault();
    console.log("Edit button clicked");
    
    const itemId = button.closest("tr").querySelector(".id")?.getAttribute("data-id");
    const tr = button.closest("tr");
    
    if (!itemId) {
        console.error("Item ID not found");
        return;
    }
    
    // Get data from the table row
    const schoolClassId = tr.querySelector(".sclass")?.getAttribute("data-schoolclassid") || "";
    const subjectTeacherId = tr.querySelector(".subjectteacher")?.getAttribute("data-subteacherid") || "";
    
    console.log("Edit data:", { itemId, schoolClassId, subjectTeacherId });
    
    // Populate form fields
    if (editIdField) editIdField.value = itemId;
    if (editSchoolClassIdField) editSchoolClassIdField.value = schoolClassId;
    
    // Clear all checkboxes first
    document.querySelectorAll('#editModal input[name="subjectteacherid[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Check the appropriate subject teacher checkbox
    if (subjectTeacherId) {
        const teacherCheckbox = document.querySelector(`#editModal input[name="subjectteacherid[]"][value="${subjectTeacherId}"]`);
        if (teacherCheckbox) {
            teacherCheckbox.checked = true;
        }
    }

    try {
        const modal = new bootstrap.Modal(document.getElementById("editModal"));
        modal.show();
        console.log("Edit modal opened with data populated");
    } catch (error) {
        console.error("Error opening edit modal:", error);
        Swal.fire({
            position: "center",
            icon: "error",
            title: "Error opening edit modal",
            text: "Please try again or contact support.",
            showConfirmButton: true
        });
    }
}

// Clear form fields
function clearAddFields() {
    if (addIdField) addIdField.value = "";
    if (addSchoolClassIdField) addSchoolClassIdField.value = "";
    document.querySelectorAll('#addSubjectClassModal input[name="subjectteacherid[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function clearEditFields() {
    if (editIdField) editIdField.value = "";
    if (editSchoolClassIdField) editSchoolClassIdField.value = "";
    document.querySelectorAll('#editModal input[name="subjectteacherid[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

// Delete multiple subject classes
function deleteMultiple() {
    console.log("Delete multiple triggered");
    
    const ids_array = [];
    const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');
    
    checkboxes.forEach((checkbox) => {
        const id = checkbox.closest("tr").querySelector(".id")?.getAttribute("data-id");
        if (id) ids_array.push(id);
    });
    
    if (ids_array.length === 0) {
        Swal.fire({
            title: "Please select at least one checkbox",
            confirmButtonClass: "btn btn-info",
            buttonsStyling: false,
            showCloseButton: true
        });
        return;
    }
    
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
    }).then((result) => {
        if (result.isConfirmed) {
            Promise.all(ids_array.map((id) => axios.delete(`/subjectclass/${id}`)))
                .then(() => {
                    Swal.fire({
                        title: "Deleted!",
                        text: "Your subject classes have been deleted.",
                        icon: "success",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false
                    });
                    
                    setTimeout(() => refreshTable(), 1000);
                })
                .catch((error) => {
                    console.error("Bulk delete error:", error);
                    Swal.fire({
                        title: "Error!",
                        text: error.response?.data?.message || "Failed to delete subject classes",
                        icon: "error",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false
                    });
                });
        }
    });
}

// Initialize List.js for client-side filtering
let subjectClassList;

function initializeListJS() {
    const subjectClassListContainer = document.getElementById('subjectClassList');
    const hasRows = document.querySelectorAll('#subjectClassList tbody tr:not(.noresult)').length > 0;
    
    if (subjectClassListContainer && hasRows) {
        try {
            // Destroy existing instance if it exists
            if (subjectClassList) {
                subjectClassList.clear();
            }
            
            subjectClassList = new List('subjectClassList', {
                valueNames: ['sn', 'subjectteacher', 'subject', 'sclass', 'schoolarm', 'term', 'session', 'datereg'],
                page: 1000,
                pagination: false,
                listClass: 'list'
            });
            
            console.log("List.js initialized/reinitialized");
            
            // Update no results message
            subjectClassList.on('searchComplete', function () {
                const noResultRow = document.querySelector('.noresult');
                if (noResultRow) {
                    noResultRow.style.display = subjectClassList.visibleItems.length === 0 ? 'block' : 'none';
                }
            });
            
        } catch (error) {
            console.error("List.js initialization failed:", error);
        }
    } else {
        console.warn("No subject classes available for List.js initialization");
    }
}

// Filter data (client-side)
function filterData() {
    const searchInput = document.querySelector(".search-box input.search");
    const searchValue = searchInput?.value || "";
    
    console.log("Filtering with search:", searchValue);
    
    if (subjectClassList) {
        subjectClassList.search(searchValue, ['sn', 'subjectteacher', 'subject', 'sclass', 'schoolarm', 'term', 'session']);
    }
}

// FIXED: Add subject class with proper modal handling
const addSubjectClassForm = document.getElementById("add-subjectclass-form");
if (addSubjectClassForm) {
    addSubjectClassForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Add form submitted at", new Date().toISOString());

        const errorMsg = document.getElementById("alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");

        const formData = new FormData(addSubjectClassForm);
        const schoolclassid = formData.get('schoolclassid');
        const subjectteacherids = formData.getAll('subjectteacherid[]');

        console.log("Form data:", { schoolclassid, subjectteacherids });

        if (!schoolclassid || schoolclassid === "") {
            if (errorMsg) {
                errorMsg.innerHTML = "Please select a class";
                errorMsg.classList.remove("d-none");
            }
            return;
        }
        
        if (subjectteacherids.length === 0) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please select at least one subject teacher";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        // Disable submit button to prevent double submission
        const submitBtn = document.getElementById("add-btn");
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = "Adding...";
        }

        console.log("Sending add request:", { schoolclassid, subjectteacherids });

        axios.post('/subjectclass', {
            schoolclassid,
            subjectteacherid: subjectteacherids
        }, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(function (response) {
            console.log("Add success:", response.data);
            
            // Get modal instance and hide it properly
            const modalElement = document.getElementById("addSubjectClassModal");
            const modal = bootstrap.Modal.getInstance(modalElement);
            
            if (modal) {
                modal.hide();
            }
            
            // Wait for modal to close before showing success message
            setTimeout(() => {
                Swal.fire({
                    position: "center",
                    icon: "success",
                    title: response.data.message || "Subject Class(es) added successfully!",
                    showConfirmButton: false,
                    timer: 2000,
                    showCloseButton: true
                });
                
                // Refresh the table after showing success message
                setTimeout(() => refreshTable(), 500);
            }, 300);
        })
        .catch(function (error) {
            console.error("Add error:", error.response?.data || error);
            
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = "Add Subject Class";
            }
            
            if (errorMsg) {
                errorMsg.innerHTML = error.response?.data?.message ||
                    Object.values(error.response?.data?.errors || {}).flat().join(", ") ||
                    "Error adding subject class";
                errorMsg.classList.remove("d-none");
            }
        });
    });
}

// FIXED: Edit subject class with proper modal handling
const editSubjectClassForm = document.getElementById("edit-subjectclass-form");
if (editSubjectClassForm) {
    editSubjectClassForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Edit form submitted");
        
        const errorMsg = document.getElementById("edit-alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
        
        const formData = new FormData(editSubjectClassForm);
        const schoolclassid = formData.get('schoolclassid');
        const subjectteacherids = formData.getAll('subjectteacherid[]');
        const id = editIdField?.value;
        
        if (!id || !schoolclassid || subjectteacherids.length === 0) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please select a class and at least one subject teacher";
                errorMsg.classList.remove("d-none");
                console.warn("Form validation failed: Invalid ID, class, or subject teacher");
            }
            return;
        }
        
        // Disable submit button
        const submitBtn = document.getElementById("update-btn");
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = "Updating...";
        }
        
        console.log("Sending edit request:", { id, schoolclassid, subjectteacherids });
        
        // For edit, we'll send the first selected subject teacher
        const subjectteacherid = subjectteacherids[0];
        
        axios.put(`/subjectclass/${id}`, { schoolclassid, subjectteacherid })
            .then(function (response) {
                console.log("Edit success:", response.data);
                
                // Get modal instance and hide it properly
                const modalElement = document.getElementById("editModal");
                const modal = bootstrap.Modal.getInstance(modalElement);
                
                if (modal) {
                    modal.hide();
                }
                
                // Wait for modal to close before showing success message
                setTimeout(() => {
                    Swal.fire({
                        position: "center",
                        icon: "success",
                        title: response.data.message || "Subject Class updated successfully!",
                        showConfirmButton: false,
                        timer: 2000,
                        showCloseButton: true
                    });
                    
                    // Refresh the table after showing success message
                    setTimeout(() => refreshTable(), 500);
                }, 300);
            })
            .catch(function (error) {
                console.error("Edit error:", error.response?.data || error);
                
                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = "Update";
                }
                
                if (errorMsg) {
                    errorMsg.innerHTML = error.response?.data?.message ||
                        Object.values(error.response?.data?.errors || {}).flat().join(", ") ||
                        "Error updating subject class";
                    errorMsg.classList.remove("d-none");
                }
            });
    });
}

// FIXED: Modal events with proper cleanup
const addModal = document.getElementById("addSubjectClassModal");
if (addModal) {
    addModal.addEventListener("show.bs.modal", function (e) {
        console.log("Add modal show event");
        
        const modalLabel = document.getElementById("exampleModalLabel");
        const addBtn = document.getElementById("add-btn");
        
        if (modalLabel) modalLabel.innerHTML = "Add Subject Class";
        if (addBtn) {
            addBtn.innerHTML = "Add Subject Class";
            addBtn.disabled = true;
        }
        
        const updateSubmitButton = () => {
            const schoolclassid = document.getElementById("schoolclassid")?.value;
            const checkedTeachers = document.querySelectorAll('#addSubjectClassModal input[name="subjectteacherid[]"]:checked').length;
            if (addBtn) addBtn.disabled = !schoolclassid || checkedTeachers === 0;
        };
        
        document.getElementById("schoolclassid")?.addEventListener("change", updateSubmitButton);
        document.querySelectorAll('#addSubjectClassModal input[name="subjectteacherid[]"]').forEach(cb => {
            cb.addEventListener("change", updateSubmitButton);
        });
    });
    
    addModal.addEventListener("hidden.bs.modal", function () {
        console.log("Add modal hidden - cleaning up");
        clearAddFields();
        const errorMsg = document.getElementById("alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
        
        // Reset submit button
        const addBtn = document.getElementById("add-btn");
        if (addBtn) {
            addBtn.disabled = true;
            addBtn.innerHTML = "Add Subject Class";
        }
        
        // Remove modal backdrop if it's stuck
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        
        // Ensure body classes are cleaned up
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });
}

const editModal = document.getElementById("editModal");
if (editModal) {
    editModal.addEventListener("show.bs.modal", function () {
        console.log("Edit modal show event");
        
        const modalLabel = document.getElementById("editModalLabel");
        const updateBtn = document.getElementById("update-btn");
        
        if (modalLabel) modalLabel.innerHTML = "Edit Subject Class";
        if (updateBtn) {
            updateBtn.innerHTML = "Update";
            updateBtn.disabled = false;
        }
    });
    
    editModal.addEventListener("hidden.bs.modal", function () {
        console.log("Edit modal hidden - cleaning up");
        clearEditFields();
        const errorMsg = document.getElementById("edit-alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
        
        // Reset submit button
        const updateBtn = document.getElementById("update-btn");
        if (updateBtn) {
            updateBtn.disabled = false;
            updateBtn.innerHTML = "Update";
        }
        
        // Remove modal backdrop if it's stuck
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        
        // Ensure body classes are cleaned up
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });
}

// Initialize listeners
document.addEventListener("DOMContentLoaded", function () {
    console.log("DOMContentLoaded fired");
    
    const searchInput = document.querySelector(".search-box input.search");
    if (searchInput) {
        searchInput.addEventListener("input", debounce(function () {
            console.log("Search input changed:", searchInput.value);
            filterData();
        }, 300));
    } else {
        console.error("Search input not found");
    }
    
    initializeCheckboxes();
    initializeListJS();
});

// Expose functions to global scope
window.deleteMultiple = deleteMultiple;