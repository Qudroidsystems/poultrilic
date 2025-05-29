console.log("classcategory.init.js is loaded and executing!");

// Verify dependencies
try {
    if (typeof axios === 'undefined') throw new Error("Axios is not loaded");
    if (typeof Swal === 'undefined') throw new Error("SweetAlert2 is not loaded");
    if (typeof bootstrap === 'undefined') throw new Error("Bootstrap is not loaded");
    if (typeof List === 'undefined') throw new Error("List.js is not loaded");
} catch (error) {
    console.error("Dependency check failed:", error);
}

// Set Axios CSRF token globally
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

// Debounce function for search input
function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Check all checkbox
var checkAll = document.getElementById("checkAll");
if (checkAll) {
    checkAll.onclick = function () {
        console.log("checkAll clicked");
        var checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
        checkboxes.forEach((checkbox) => {
            checkbox.checked = this.checked;
            const row = checkbox.closest("tr");
            if (checkbox.checked) {
                row.classList.add("table-active");
            } else {
                row.classList.remove("table-active");
            }
        });
        const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
        var removeActions = document.getElementById("remove-actions");
        if (removeActions) {
            removeActions.classList.toggle("d-none", checkedCount === 0);
        }
    };
}

// Form fields
var addIdField = document.getElementById("add-id-field");
var addCategoryField = document.getElementById("category");
var addCa1ScoreField = document.getElementById("ca1score");
var addCa2ScoreField = document.getElementById("ca2score");
var addCa3ScoreField = document.getElementById("ca3score");
var addExamScoreField = document.getElementById("examscore");
var addTotalScoreField = document.getElementById("total_score");
var addSubmitButton = document.getElementById("add-btn");
var editIdField = document.getElementById("edit-id-field");
var editCategoryField = document.getElementById("edit-category");
var editCa1ScoreField = document.getElementById("edit-ca1score");
var editCa2ScoreField = document.getElementById("edit-ca2score");
var editCa3ScoreField = document.getElementById("edit-ca3score");
var editExamScoreField = document.getElementById("edit-examscore");
var editTotalScoreField = document.getElementById("edit-total_score");
var editSubmitButton = document.getElementById("update-btn");

// Calculate total score for Add Modal
function calculateAddTotalScore() {
    const ca1 = parseFloat(addCa1ScoreField.value) || 0;
    const ca2 = parseFloat(addCa2ScoreField.value) || 0;
    const ca3 = parseFloat(addCa3ScoreField.value) || 0;
    const exam = parseFloat(addExamScoreField.value) || 0;
    const total = ca1 + ca2 + ca3 + exam;
    if (addTotalScoreField) addTotalScoreField.value = total;
    if (addSubmitButton) addSubmitButton.disabled = total !== 100;
}

// Calculate total score for Edit Modal
function calculateEditTotalScore() {
    const ca1 = parseFloat(editCa1ScoreField.value) || 0;
    const ca2 = parseFloat(editCa2ScoreField.value) || 0;
    const ca3 = parseFloat(editCa3ScoreField.value) || 0;
    const exam = parseFloat(editExamScoreField.value) || 0;
    const total = ca1 + ca2 + ca3 + exam;
    if (editTotalScoreField) editTotalScoreField.value = total;
    if (editSubmitButton) editSubmitButton.disabled = total !== 100;
}

// Add event listeners for Add Modal score inputs
[addCa1ScoreField, addCa2ScoreField, addCa3ScoreField, addExamScoreField].forEach(field => {
    if (field) {
        field.addEventListener('input', calculateAddTotalScore);
    }
});

// Add event listeners for Edit Modal score inputs
[editCa1ScoreField, editCa2ScoreField, editCa3ScoreField, editExamScoreField].forEach(field => {
    if (field) {
        field.addEventListener('input', calculateEditTotalScore);
    }
});

// Checkbox handling
function ischeckboxcheck() {
    const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
    checkboxes.forEach((checkbox) => {
        checkbox.removeEventListener("change", handleCheckboxChange);
        checkbox.addEventListener("change", handleCheckboxChange);
    });
}

function handleCheckboxChange(e) {
    const row = e.target.closest("tr");
    if (e.target.checked) {
        row.classList.add("table-active");
    } else {
        row.classList.remove("table-active");
    }
    const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
    var removeActions = document.getElementById("remove-actions");
    if (removeActions) {
        removeActions.classList.toggle("d-none", checkedCount === 0);
    }
    const allCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]');
    if (checkAll) {
        checkAll.checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCount;
    }
}

// Event delegation for edit and remove buttons
document.addEventListener('click', function (e) {
    if (e.target.closest('.edit-item-btn')) {
        handleEditClick(e);
    } else if (e.target.closest('.remove-item-btn')) {
        handleRemoveClick(e);
    }
});

// Delete single category
function handleRemoveClick(e) {
    e.preventDefault();
    var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
    var deleteButton = document.getElementById("delete-record");
    if (deleteButton) {
        deleteButton.addEventListener("click", function () {
            axios.delete(`/classcategories/${itemId}`).then(function () {
                Swal.fire({
                    position: "center",
                    icon: "success",
                    title: "Class category deleted successfully!",
                    showConfirmButton: false,
                    timer: 2000,
                    showCloseButton: true
                });
                window.location.reload();
            }).catch(function (error) {
                Swal.fire({
                    position: "center",
                    icon: "error",
                    title: "Error deleting class category",
                    text: error.response?.data?.message || "An error occurred",
                    showConfirmButton: true
                });
            });
        }, { once: true });
    }
    try {
        var modal = new bootstrap.Modal(document.getElementById("deleteRecordModal"));
        modal.show();
    } catch (error) {
        console.error("Error opening delete modal:", error);
    }
}

// Edit category
function handleEditClick(e) {
    e.preventDefault();
    var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
    var tr = e.target.closest("tr");
    if (editIdField) editIdField.value = itemId;
    if (editCategoryField) editCategoryField.value = tr.querySelector(".category").innerText;
    if (editCa1ScoreField) editCa1ScoreField.value = tr.querySelector(".ca1score").innerText;
    if (editCa2ScoreField) editCa2ScoreField.value = tr.querySelector(".ca2score").innerText;
    if (editCa3ScoreField) editCa3ScoreField.value = tr.querySelector(".ca3score").innerText;
    if (editExamScoreField) editExamScoreField.value = tr.querySelector(".examscore").innerText;
    calculateEditTotalScore(); // Calculate total when modal is populated
    try {
        var modal = new bootstrap.Modal(document.getElementById("editModal"));
        modal.show();
    } catch (error) {
        console.error("Error opening edit modal:", error);
    }
}

// Clear form fields
function clearAddFields() {
    if (addIdField) addIdField.value = "";
    if (addCategoryField) addCategoryField.value = "";
    if (addCa1ScoreField) addCa1ScoreField.value = "";
    if (addCa2ScoreField) addCa2ScoreField.value = "";
    if (addCa3ScoreField) addCa3ScoreField.value = "";
    if (addExamScoreField) addExamScoreField.value = "";
    if (addTotalScoreField) addTotalScoreField.value = "";
    if (addSubmitButton) addSubmitButton.disabled = true;
}

function clearEditFields() {
    if (editIdField) editIdField.value = "";
    if (editCategoryField) editCategoryField.value = "";
    if (editCa1ScoreField) editCa1ScoreField.value = "";
    if (editCa2ScoreField) editCa2ScoreField.value = "";
    if (editCa3ScoreField) editCa3ScoreField.value = "";
    if (editExamScoreField) editExamScoreField.value = "";
    if (editTotalScoreField) editTotalScoreField.value = "";
    if (editSubmitButton) editSubmitButton.disabled = true;
}

// Delete multiple categories
function deleteMultiple() {
    const ids_array = [];
    const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
    checkboxes.forEach((checkbox) => {
        if (checkbox.checked) {
            const id = checkbox.closest("tr").querySelector(".id").getAttribute("data-id");
            ids_array.push(id);
        }
    });
    if (ids_array.length > 0) {
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
            if (result.value) {
                Promise.all(ids_array.map((id) => {
                    return axios.delete(`/classcategories/${id}`);
                })).then(() => {
                    Swal.fire({
                        title: "Deleted!",
                        text: "Your class categories have been deleted.",
                        icon: "success",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false
                    });
                    window.location.reload();
                }).catch((error) => {
                    Swal.fire({
                        title: "Error!",
                        text: error.response?.data?.message || "Failed to delete class categories",
                        icon: "error",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false
                    });
                });
            }
        });
    } else {
        Swal.fire({
            title: "Please select at least one checkbox",
            confirmButtonClass: "btn btn-info",
            buttonsStyling: false,
            showCloseButton: true
        });
    }
}

// Initialize List.js for client-side filtering
var categoryList;
var categoryListContainer = document.getElementById('categoryList');
if (categoryListContainer && document.querySelectorAll('#categoryList tbody tr').length > 0) {
    try {
        categoryList = new List('categoryList', {
            valueNames: ['categoryid', 'category', 'ca1score', 'ca2score', 'ca3score', 'examscore', 'datereg'],
            page: 1000,
            pagination: false,
            listClass: 'list'
        });
    } catch (error) {
        console.error("List.js initialization failed:", error);
    }
} else {
    console.warn("No class categories available for List.js initialization");
}

// Update no results message
if (categoryList) {
    categoryList.on('searchComplete', function () {
        var noResultRow = document.querySelector('.noresult');
        if (categoryList.visibleItems.length === 0) {
            noResultRow.style.display = 'block';
        } else {
            noResultRow.style.display = 'none';
        }
    });
}

// Filter data (client-side)
function filterData() {
    var searchInput = document.querySelector(".search-box input.search");
    var searchValue = searchInput ? searchInput.value : "";
    console.log("Filtering with search:", searchValue);
    if (categoryList) {
        categoryList.search(searchValue, ['category', 'ca1score', 'ca2score', 'ca3score', 'examscore']);
    }
}

// Add category
var addCategoryForm = document.getElementById("add-category-form");
if (addCategoryForm) {
    addCategoryForm.addEventListener("submit", function (e) {
        e.preventDefault();
        var errorMsg = document.getElementById("alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
        var formData = new FormData(addCategoryForm);
        var category = formData.get('category');
        var ca1score = parseFloat(formData.get('ca1score')) || 0;
        var ca2score = parseFloat(formData.get('ca2score')) || 0;
        var ca3score = parseFloat(formData.get('ca3score')) || 0;
        var examscore = parseFloat(formData.get('examscore')) || 0;
        var totalScore = ca1score + ca2score + ca3score + examscore;

        if (!category || !ca1score || !ca2score || !ca3score || !examscore) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please fill in all required fields";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        if (totalScore !== 100) {
            if (errorMsg) {
                errorMsg.innerHTML = "The sum of CA1, CA2, CA3, and Exam scores must be exactly 100";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        console.log("Submitting Add Category:", { category, ca1score, ca2score, ca3score, examscore, totalScore });
        axios.post('/classcategories', {
            category: category,
            ca1score: ca1score,
            ca2score: ca2score,
            ca3score: ca3score,
            examscore: examscore
        }, {
            headers: { 'Content-Type': 'application/json' }
        }).then(function (response) {
            console.log("Add Category Success:", response.data);
            Swal.fire({
                position: "center",
                icon: "success",
                title: "Class category added successfully!",
                showConfirmButton: false,
                timer: 2000,
                showCloseButton: true
            });
            window.location.reload();
        }).catch(function (error) {
            console.error("Add Category Error:", error.response);
            if (errorMsg) {
                errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Error adding class category";
                errorMsg.classList.remove("d-none");
            }
        });
    });
}

// Edit category
var editCategoryForm = document.getElementById("edit-category-form");
if (editCategoryForm) {
    editCategoryForm.addEventListener("submit", function (e) {
        e.preventDefault();
        var errorMsg = document.getElementById("edit-alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
        var formData = new FormData(editCategoryForm);
        var category = formData.get('category');
        var ca1score = parseFloat(formData.get('ca1score')) || 0;
        var ca2score = parseFloat(formData.get('ca2score')) || 0;
        var ca3score = parseFloat(formData.get('ca3score')) || 0;
        var examscore = parseFloat(formData.get('examscore')) || 0;
        var totalScore = ca1score + ca2score + ca3score + examscore;
        var id = editIdField.value;

        if (!category || !ca1score || !ca2score || !ca3score || !examscore) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please fill in all required fields";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        if (totalScore !== 100) {
            if (errorMsg) {
                errorMsg.innerHTML = "The sum of CA1, CA2, CA3, and Exam scores must be exactly 100";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        console.log("Submitting Edit Category:", { id, category, ca1score, ca2score, ca3score, examscore, totalScore });
        axios.put(`/classcategories/${id}`, {
            category: category,
            ca1score: ca1score,
            ca2score: ca2score,
            ca3score: ca3score,
            examscore: examscore
        }, {
            headers: { 'Content-Type': 'application/json' }
        }).then(function (response) {
            console.log("Edit Category Success:", response.data);
            Swal.fire({
                position: "center",
                icon: "success",
                title: "Class category updated successfully!",
                showConfirmButton: false,
                timer: 2000,
                showCloseButton: true
            });
            window.location.reload();
        }).catch(function (error) {
            console.error("Edit Category Error:", error.response);
            if (errorMsg) {
                errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Error updating class category";
                errorMsg.classList.remove("d-none");
            }
        });
    });
}

// Modal events
var addModal = document.getElementById("addCategoryModal");
if (addModal) {
    addModal.addEventListener("show.bs.modal", function (e) {
        if (e.relatedTarget.classList.contains("add-btn")) {
            var modalLabel = document.getElementById("exampleModalLabel");
            var addBtn = document.getElementById("add-btn");
            if (modalLabel) modalLabel.innerHTML = "Add Class Category";
            if (addBtn) addBtn.innerHTML = "Add Category";
        }
    });
    addModal.addEventListener("hidden.bs.modal", function () {
        clearAddFields();
    });
}

var editModal = document.getElementById("editModal");
if (editModal) {
    editModal.addEventListener("show.bs.modal", function () {
        var modalLabel = document.getElementById("editModalLabel");
        var updateBtn = document.getElementById("update-btn");
        if (modalLabel) modalLabel.innerHTML = "Edit Class Category";
        if (updateBtn) updateBtn.innerHTML = "Update";
    });
    editModal.addEventListener("hidden.bs.modal", function () {
        clearEditFields();
    });
}

// Initialize listeners
document.addEventListener("DOMContentLoaded", function () {
    console.log("DOMContentLoaded fired");
    var searchInput = document.querySelector(".search-box input.search");
    if (searchInput) {
        searchInput.addEventListener("input", debounce(function () {
            console.log("Search input changed:", searchInput.value);
            filterData();
        }, 300));
    } else {
        console.error("Search input not found!");
    }

    ischeckboxcheck();
});

// Expose functions to global scope
window.deleteMultiple = deleteMultiple;