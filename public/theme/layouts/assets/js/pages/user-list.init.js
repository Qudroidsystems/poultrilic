var perPage = 5,
    editlist = false,
    checkAll = document.getElementById("checkAll"),
    options = {
        valueNames: ["id", "name", "email", "role", "datereg"],
    },
    userList = new List("userList", options);

console.log("Initial userList items:", userList.items.length);

userList.on("updated", function (e) {
    console.log("List.js updated, matching items:", e.matchingItems.length, "total items:", userList.items.length);
    document.getElementsByClassName("noresult")[0].style.display = e.matchingItems.length === 0 ? "block" : "none";
    setTimeout(() => {
        refreshCallbacks();
        ischeckboxcheck();
    }, 100);
});

document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM loaded, initializing List.js...");
    console.log("Initial userList items:", userList.items.length);
    refreshCallbacks();
    ischeckboxcheck();

    // Initialize Choices.js
    if (typeof Choices !== 'undefined') {
        var addRoleVal = new Choices(document.getElementById("role"), { searchEnabled: true, removeItemButton: true });
        var editRoleVal = new Choices(document.getElementById("edit-role"), { searchEnabled: true, removeItemButton: true });
        var roleFilterVal = new Choices(document.getElementById("idRole"), { searchEnabled: true });
        var emailFilterVal = new Choices(document.getElementById("idEmail"), { searchEnabled: true });
    } else {
        console.warn("Choices.js not available, falling back to native select");
    }
});

if (checkAll) {
    checkAll.onclick = function () {
        console.log("checkAll clicked");
        var checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
        console.log("checkAll clicked, checkboxes found:", checkboxes.length);
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
        document.getElementById("remove-actions").classList.toggle("d-none", checkedCount === 0);
    };
}

var addIdField = document.getElementById("add-id-field"),
    addNameField = document.getElementById("name"),
    addEmailField = document.getElementById("email"),
    addRoleField = document.getElementById("role"),
    addPasswordField = document.getElementById("password"),
    addPasswordConfirmField = document.getElementById("password_confirmation"),
    editIdField = document.getElementById("edit-id-field"),
    editNameField = document.getElementById("edit-name"),
    editEmailField = document.getElementById("edit-email"),
    editRoleField = document.getElementById("edit-role"),
    editPasswordField = document.getElementById("edit-password"),
    editPasswordConfirmField = document.getElementById("edit-password_confirmation"),
    date = new Date().toUTCString().slice(5, 16);

function ensureAxios() {
    if (typeof axios === 'undefined') {
        console.error("Axios is not defined. Please include Axios library.");
        Swal.fire({
            position: "center",
            icon: "error",
            title: "Configuration error",
            text: "Axios library is missing",
            showConfirmButton: true
        });
        return false;
    }
    return true;
}

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
    document.getElementById("remove-actions").classList.toggle("d-none", checkedCount === 0);
    const allCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]');
    document.getElementById("checkAll").checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCount;
}

function refreshCallbacks() {
    console.log("refreshCallbacks executed at", new Date().toISOString());
    var removeButtons = document.getElementsByClassName("remove-item-btn");
    var editButtons = document.getElementsByClassName("edit-item-btn");
    console.log("Attaching event listeners to", removeButtons.length, "remove buttons and", editButtons.length, "edit buttons");

    Array.from(removeButtons).forEach(function (btn) {
        btn.removeEventListener("click", handleRemoveClick);
        btn.addEventListener("click", handleRemoveClick);
    });

    Array.from(editButtons).forEach(function (btn) {
        btn.removeEventListener("click", handleEditClick);
        btn.addEventListener("click", handleEditClick);
    });
}

function handleRemoveClick(e) {
    e.preventDefault();
    try {
        var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
        console.log("Remove button clicked for ID:", itemId);
        document.getElementById("delete-record").addEventListener("click", function () {
            if (!ensureAxios()) return;
            axios.delete(`/users/${itemId}`, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(function () {
                console.log("Deleted user ID:", itemId);
                window.location.reload();
                Swal.fire({
                    position: "center",
                    icon: "success",
                    title: "User deleted successfully!",
                    showConfirmButton: false,
                    timer: 2000,
                    showCloseButton: true
                });
            }).catch(function (error) {
                console.error("Error deleting user:", error);
                Swal.fire({
                    position: "center",
                    icon: "error",
                    title: "Error deleting user",
                    text: error.response?.data?.message || "An error occurred",
                    showConfirmButton: true
                });
            });
        }, { once: true });
        var modal = new bootstrap.Modal(document.getElementById("deleteRecordModal"));
        modal.show();
    } catch (error) {
        console.error("Error in remove-item-btn click:", error);
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Failed to initiate delete",
            showConfirmButton: true
        });
    }
}

function handleEditClick(e) {
    e.preventDefault();
    try {
        var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
        var tr = e.target.closest("tr");
        console.log("Edit button clicked for ID:", itemId);
        editlist = true;
        editIdField.value = itemId;
        editNameField.value = tr.querySelector(".name a").innerText;
        editEmailField.value = tr.querySelector(".email").innerText;
        var roles = tr.querySelector(".role").getAttribute("data-role")?.split(",") || [];
        if (typeof Choices !== 'undefined' && editRoleVal) {
            editRoleVal.setChoiceByValue(roles.filter(role => role.trim()));
        } else {
            console.warn("Choices.js not available, using native select");
            Array.from(editRoleField.options).forEach(option => {
                option.selected = roles.includes(option.value);
            });
        }
        var modal = new bootstrap.Modal(document.getElementById("editModal"));
        modal.show();
    } catch (error) {
        console.error("Error in edit-item-btn click:", error);
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Failed to populate edit modal",
            showConfirmButton: true
        });
    }
}

function clearAddFields() {
    addIdField.value = "";
    addNameField.value = "";
    addEmailField.value = "";
    addPasswordField.value = "";
    addPasswordConfirmField.value = "";
    if (typeof Choices !== 'undefined' && addRoleVal) {
        addRoleVal.setChoiceByValue([]);
    } else {
        Array.from(addRoleField.options).forEach(option => option.selected = false);
    }
}

function clearEditFields() {
    editIdField.value = "";
    editNameField.value = "";
    editEmailField.value = "";
    editPasswordField.value = "";
    editPasswordConfirmField.value = "";
    if (typeof Choices !== 'undefined' && editRoleVal) {
        editRoleVal.setChoiceByValue([]);
    } else {
        Array.from(editRoleField.options).forEach(option => option.selected = false);
    }
}

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
                if (!ensureAxios()) return;
                Promise.all(ids_array.map((id) => {
                    return axios.delete(`/users/${id}`, {
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                })).then(() => {
                    window.location.reload();
                    Swal.fire({
                        title: "Deleted!",
                        text: "Your data has been deleted.",
                        icon: "success",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false
                    });
                }).catch((error) => {
                    console.error("Error deleting users:", error);
                    Swal.fire({
                        title: "Error!",
                        text: error.response?.data?.message || "Failed to delete users",
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

function filterData() {
    var searchInput = document.querySelector(".search-box input.search").value.toLowerCase();
    var roleSelect = document.getElementById("idRole");
    var emailSelect = document.getElementById("idEmail");
    var selectedRole = typeof Choices !== 'undefined' && roleFilterVal ? roleFilterVal.getValue(true) : roleSelect.value;
    var selectedEmail = typeof Choices !== 'undefined' && emailFilterVal ? emailFilterVal.getValue(true) : emailSelect.value;

    console.log("Filtering with:", { search: searchInput, role: selectedRole, email: selectedEmail });

    userList.filter(function (item) {
        var nameMatch = item.values().name.toLowerCase().includes(searchInput);
        var emailMatch = item.values().email.toLowerCase().includes(searchInput);
        var roleMatch = selectedRole === "all" || item.values().role.split(",").includes(selectedRole);
        var emailSelectMatch = selectedEmail === "all" || item.values().email === selectedEmail;

        return (nameMatch || emailMatch) && roleMatch && emailSelectMatch;
    });
}

document.getElementById("add-user-form").addEventListener("submit", function (e) {
    e.preventDefault();
    var errorMsg = document.getElementById("alert-error-msg");
    errorMsg.classList.remove("d-none");
    setTimeout(() => errorMsg.classList.add("d-none"), 2000);

    if (addNameField.value === "") {
        errorMsg.innerHTML = "Please enter a name";
        return false;
    }
    if (addEmailField.value === "") {
        errorMsg.innerHTML = "Please enter an email";
        return false;
    }
    if (!addRoleField.selectedOptions.length) {
        errorMsg.innerHTML = "Please select at least one role";
        return false;
    }
    if (addPasswordField.value === "") {
        errorMsg.innerHTML = "Please enter a password";
        return false;
    }
    if (addPasswordField.value !== addPasswordConfirmField.value) {
        errorMsg.innerHTML = "Passwords do not match";
        return false;
    }

    if (!ensureAxios()) return;

    var roles = Array.from(addRoleField.selectedOptions).map(option => option.value);
    axios.post('/users', {
        name: addNameField.value,
        email: addEmailField.value,
        roles: roles,
        password: addPasswordField.value,
        password_confirmation: addPasswordConfirmField.value,
        _token: document.querySelector('meta[name="csrf-token"]').content
    }).then(function (response) {
        window.location.reload();
        Swal.fire({
            position: "center",
            icon: "success",
            title: "User added successfully!",
            showConfirmButton: false,
            timer: 2000,
            showCloseButton: true
        });
    }).catch(function (error) {
        console.error("Error adding user:", error);
        var message = error.response?.data?.message || "Error adding user";
        if (error.response?.status === 422) {
            message = Object.values(error.response.data.errors || {}).flat().join(", ");
        }
        errorMsg.innerHTML = message;
    });
});

document.getElementById("edit-user-form").addEventListener("submit", function (e) {
    e.preventDefault();
    var errorMsg = document.getElementById("alert-error-msg");
    errorMsg.classList.remove("d-none");
    setTimeout(() => errorMsg.classList.add("d-none"), 2000);

    if (editNameField.value === "") {
        errorMsg.innerHTML = "Please enter a name";
        return false;
    }
    if (editEmailField.value === "") {
        errorMsg.innerHTML = "Please enter an email";
        return false;
    }
    if (!editRoleField.selectedOptions.length) {
        errorMsg.innerHTML = "Please select at least one role";
        return false;
    }
    if (editPasswordField.value !== "" && editPasswordField.value !== editPasswordConfirmField.value) {
        errorMsg.innerHTML = "Passwords do not match";
        return false;
    }

    if (!ensureAxios()) return;

    var data = {
        name: editNameField.value,
        email: editEmailField.value,
        roles: Array.from(editRoleField.selectedOptions).map(option => option.value),
        _token: document.querySelector('meta[name="csrf-token"]').content
    };
    if (editPasswordField.value) {
        data.password = editPasswordField.value;
        data.password_confirmation = editPasswordConfirmField.value;
    }

    axios.put(`/users/${editIdField.value}`, data)
        .then(function (response) {
            window.location.reload();
            Swal.fire({
                position: "center",
                icon: "success",
                title: "User updated successfully!",
                showConfirmButton: false,
                timer: 2000,
                showCloseButton: true
            });
        })
        .catch(function (error) {
            console.error("Error updating user:", error);
            var message = error.response?.data?.message || "Error updating user";
            if (error.response?.status === 422) {
                message = Object.values(error.response.data.errors || {}).flat().join(", ");
            }
            errorMsg.innerHTML = message;
        });
});

document.getElementById("showModal").addEventListener("show.bs.modal", function (e) {
    if (e.relatedTarget.classList.contains("add-btn")) {
        console.log("Opening showModal for adding user...");
        document.getElementById("addModalLabel").innerHTML = "Add User";
        document.getElementById("add-btn").innerHTML = "Add User";
    }
});

document.getElementById("editModal").addEventListener("show.bs.modal", function () {
    console.log("Opening editModal...");
    document.getElementById("editModalLabel").innerHTML = "Edit User";
    document.getElementById("update-btn").innerHTML = "Update";
});

document.getElementById("showModal").addEventListener("hidden.bs.modal", function () {
    console.log("showModal closed, clearing fields...");
    clearAddFields();
});

document.getElementById("editModal").addEventListener("hidden.bs.modal", function () {
    console.log("editModal closed, clearing fields...");
    clearEditFields();
});