

// Ensure Axios is available
function ensureAxios() {
    if (typeof axios === 'undefined') {
        console.error('Error: Axios is not defined');
        Swal.fire({
            title: "Error!",
            text: "Axios library is missing",
            icon: "error",
            confirmButtonClass: "btn btn-primary",
            buttonsStyling: true
        });
        return false;
    }
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) {
        console.error('Error: CSRF token not found');
        Swal.fire({
            title: "Error!",
            text: "CSRF token is missing",
            icon: "error",
            confirmButtonClass: "btn btn-primary",
            buttonsStyling: true
        });
        return false;
    }
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    return true;
}

// Populate States
function populateStates(stateSelectId, lgaSelectId) {
    const stateSelect = document.getElementById(stateSelectId);
    stateSelect.innerHTML = '<option value="">Select State</option>';

    axios.get('/states_lgas.json').then((response) => {
        console.log("States data received:", response.data);
        let states = response.data;

        if (Array.isArray(states)) {
            states = states.map(item => ({
                name: item.state,
                lgas: item.lgas
            }));
        } else if (states && Array.isArray(states.states)) {
            states = states.states;
        } else {
            throw new Error("Invalid or empty states data format");
        }

        if (!states.length) {
            throw new Error("No states data available");
        }

        states.forEach(state => {
            if (!state.name) {
                console.warn("Skipping state with missing name:", state);
                return;
            }
            const option = document.createElement('option');
            option.value = state.name;
            option.textContent = state.name;
            stateSelect.appendChild(option);
        });

        // Event listener for state change to populate LGAs
        stateSelect.addEventListener('change', function () {
            populateLGAs(this.value, lgaSelectId);
        });
    }).catch((error) => {
        console.error('Error loading states:', error.message, error.response?.status);
        Swal.fire({
            title: "Error!",
            text: error.response?.status === 404 
                ? "States data file not found at /states_lgas.json"
                : error.message || "Failed to load states",
            icon: "error",
            confirmButtonClass: "btn btn-primary",
            buttonsStyling: true
        });
        // Fallback: Add a manual input option
        const option = document.createElement('option');
        option.value = "Other";
        option.textContent = "Other (Enter manually)";
        stateSelect.appendChild(option);
    });
}

// Populate LGAs based on selected state
function populateLGAs(state, lgaSelectId) {
    const lgaSelect = document.getElementById(lgaSelectId);
    lgaSelect.innerHTML = '<option value="">Select Local Government</option>';

    if (!state || state === 'Other') {
        const option = document.createElement('option');
        option.value = "Other";
        option.textContent = "Other (Enter manually)";
        lgaSelect.appendChild(option);
        return;
    }

    axios.get('/states_lgas.json').then((response) => {
        console.log("LGAs data received for state:", state, response.data);
        let states = response.data;

        if (Array.isArray(states)) {
            states = states.map(item => ({
                name: item.state,
                lgas: item.lgas
            }));
        } else if (states && Array.isArray(states.states)) {
            states = states.states;
        } else {
            throw new Error("Invalid states data format");
        }

        const selectedState = states.find(s => s.name === state);
        if (selectedState && Array.isArray(selectedState.lgas)) {
            selectedState.lgas.forEach(lga => {
                const option = document.createElement('option');
                option.value = lga;
                option.textContent = lga;
                lgaSelect.appendChild(option);
            });
        } else {
            console.warn("No LGAs found for state:", state);
            const option = document.createElement('option');
            option.value = "Other";
            option.textContent = "Other (Enter manually)";
            lgaSelect.appendChild(option);
        }
    }).catch((error) => {
        console.error('Error loading LGAs:', error.message, error.response?.status);
        Swal.fire({
            title: "Error!",
            text: error.response?.status === 404 
                ? "States data file not found at /states_lgas.json"
                : error.message || "Failed to load LGAs",
            icon: "error",
            confirmButtonClass: "btn btn-primary",
            buttonsStyling: true
        });
        // Fallback: Add a manual input option
        const option = document.createElement('option');
        option.value = "Other";
        option.textContent = "Other (Enter manually)";
        lgaSelect.appendChild(option);
    });
}

// Initialize List.js for table sorting and searching
let studentList;
function initializeList() {
    const options = {
        valueNames: ['name', 'admissionNo', 'class', 'status', 'gender', 'datereg'],
        page: 10,
        pagination: true
    };
    studentList = new List('studentList', options);
    studentList.on('updated', function () {
        document.querySelector('.noresult').style.display = studentList.visibleItems.length === 0 ? 'block' : 'none';
        document.querySelector('.fw-semibold').textContent = studentList.visibleItems.length;
    });
}

// Filter Data
function filterData() {
    const search = document.querySelector('.search').value.toLowerCase();
    const classId = document.getElementById('idClass').value;
    const statusId = document.getElementById('idStatus').value;
    const gender = document.getElementById('idGender').value;

    studentList.filter(item => {
        const name = item.values().name.toLowerCase();
        const admissionNo = item.values().admissionNo.toLowerCase();
        const classValue = item.elm.querySelector('.class').dataset.class;
        const statusValue = item.elm.querySelector('.status').dataset.status;
        const genderValue = item.elm.querySelector('.gender').dataset.gender;

        const matchesSearch = name.includes(search) || admissionNo.includes(search);
        const matchesClass = classId === 'all' || classValue === classId;
        const matchesStatus = statusId === 'all' || statusValue === statusId;
        const matchesGender = gender === 'all' || genderValue === gender;

        return matchesSearch && matchesClass && matchesStatus && matchesGender;
    });
}

// Delete Multiple Students
function deleteMultiple() {
    const ids = Array.from(document.querySelectorAll('input[name="chk_child"]:checked'))
        .map(checkbox => checkbox.closest('tr').querySelector('.id').dataset.id);

    if (ids.length === 0) {
        Swal.fire({
            title: "Error!",
            text: "Please select at least one student",
            icon: "error",
            confirmButtonClass: "btn btn-primary",
            buttonsStyling: true
        });
        return;
    }

    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn btn-primary",
        cancelButtonClass: "btn btn-light",
        buttonsStyling: true
    }).then((result) => {
        if (result.isConfirmed && ensureAxios()) {
            axios.post('/students/destroy-multiple', { ids }).then(() => {
                ids.forEach(id => {
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    if (row) row.remove();
                });
                studentList.reIndex();
                Swal.fire({
                    title: "Deleted!",
                    text: "Students have been deleted",
                    icon: "success",
                    confirmButtonClass: "btn btn-primary",
                    buttonsStyling: true
                });
                document.getElementById('checkAll').checked = false;
                document.getElementById('remove-actions').classList.add('d-none');
            }).catch((error) => {
                console.error('Error deleting students:', error);
                Swal.fire({
                    title: "Error!",
                    text: error.response?.data?.message || "Failed to delete students",
                    icon: "error",
                    confirmButtonClass: "btn btn-primary",
                    buttonsStyling: true
                });
            });
        }
    });
}

document.addEventListener("DOMContentLoaded", function () {
    // Initialize List.js
    initializeList();

    // Initialize Choices.js for select elements
    if (typeof Choices !== 'undefined') {
        document.querySelectorAll('[data-choices]').forEach(element => {
            new Choices(element, {
                searchEnabled: element.dataset.choicesSearchFalse !== undefined,
                removeItemButton: element.dataset.choicesRemoveitem !== undefined
            });
        });
    }

    // Populate state and LGA dropdowns
    populateStates('addState', 'addLocal');
    populateStates('editState', 'editLocal');

    // Filter event listeners
    document.querySelector('.search').addEventListener('input', filterData);
    document.getElementById('idClass').addEventListener('change', filterData);
    document.getElementById('idStatus').addEventListener('change', filterData);
    document.getElementById('idGender').addEventListener('change', filterData);

    // Check all checkbox
    document.getElementById('checkAll').addEventListener('change', function () {
        document.querySelectorAll('input[name="chk_child"]').forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        document.getElementById('remove-actions').classList.toggle('d-none', !this.checked);
    });

    // Individual checkboxes
    document.querySelectorAll('input[name="chk_child"]').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const allChecked = document.querySelectorAll('input[name="chk_child"]').length ===
                document.querySelectorAll('input[name="chk_child"]:checked').length;
            document.getElementById('checkAll').checked = allChecked;
            document.getElementById('remove-actions').classList.toggle('d-none',
                document.querySelectorAll('input[name="chk_child"]:checked').length === 0);
        });
    });

    // Add Student Form Submission
    document.getElementById('addStudentForm').addEventListener('submit', function (e) {
        e.preventDefault();
        if (!ensureAxios()) return;

        const formData = new FormData(this);
        axios.post(this.action, formData).then((response) => {
            Swal.fire({
                title: "Success!",
                text: "Student added successfully",
                icon: "success",
                confirmButtonClass: "btn btn-primary",
                buttonsStyling: true
            }).then(() => {
                window.location.reload();
            });
        }).catch((error) => {
            console.error('Error adding student:', error);
            Swal.fire({
                title: "Error!",
                text: error.response?.data?.message || "Failed to add student",
                icon: "error",
                confirmButtonClass: "btn btn-primary",
                buttonsStyling: true
            });
        });
    });

    // Edit Student Modal Population - Fixed version
    document.querySelectorAll(".edit-item-btn").forEach((button) => {
        button.addEventListener("click", function () {
            const id = this.getAttribute("data-id");
            console.log("Edit button clicked for student ID:", id);
            if (!ensureAxios()) return;

            axios.get(`/student/${id}/edit`).then((response) => {
                console.log("Student data received:", response.data);
                const student = response.data.student;
                if (!student) {
                    throw new Error("Student data is empty");
                }

                // Populate basic fields
                const fields = [
                    { id: "editStudentId", value: student.id },
                    { id: "editAdmissionNo", value: student.admissionNo },
                    { id: "editTittle", value: student.title },
                    { id: "editFirstname", value: student.firstname },
                    { id: "editLastname", value: student.lastname },
                    { id: "editOthername", value: student.othername || '' },
                    { id: "editHomeAddress", value: student.home_address },
                    { id: "editHomeAddress2", value: student.home_address2 },
                    { id: "editDOB", value: student.dateofbirth },
                    { id: "editPlaceofbirth", value: student.placeofbirth },
                    { id: "editNationality", value: student.nationality || '' },
                    { id: "editReligion", value: student.religion || '' },
                    { id: "editLastSchool", value: student.last_school },
                    { id: "editLastClass", value: student.last_class },
                    { id: "editSchoolclassid", value: student.schoolclassid },
                    { id: "editTermid", value: student.termid },
                    { id: "editSessionid", value: student.sessionid }
                ];

                fields.forEach(({ id, value }) => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.value = value || '';
                    } else {
                        console.warn(`Element with ID '${id}' not found`);
                    }
                });

                // Handle gender radio buttons
                const genderRadios = document.querySelectorAll('input[name="gender"]');
                genderRadios.forEach(radio => {
                    radio.checked = (radio.value === student.gender);
                });
                console.log(`Set gender to: ${student.gender}`);

                // Handle age
                if (student.dateofbirth) {
                    showage(student.dateofbirth, 'editAge');
                } else if (student.age) {
                    document.getElementById('editAge').value = student.age;
                }

                // Handle student status radio buttons
                const statusRadios = document.querySelectorAll('input[name="statusId"]');
                statusRadios.forEach(radio => {
                    radio.checked = (parseInt(radio.value) === parseInt(student.statusId));
                });
                console.log(`Set status to: ${student.statusId}`);

                // Handle avatar
                const avatarElement = document.getElementById("editStudentAvatar");
                if (avatarElement) {
                    avatarElement.src = student.picture ? `/storage/${student.picture}` : '/theme/layouts/assets/media/avatars/blank.png';
                    avatarElement.setAttribute('data-original-src', student.picture ? `/storage/${student.picture}` : '/theme/layouts/assets/media/avatars/blank.png');
                }

                // Handle state and LGA
                const stateSelect = document.getElementById("editState");
                const lgaSelect = document.getElementById("editLocal");
                
                if (student.state && stateSelect) {
                    // Set state value
                    stateSelect.value = student.state;
                    
                    // Populate LGAs after a small delay to ensure state is set
                    setTimeout(() => {
                        populateLGAs(student.state, 'editLocal');
                        
                        // Set LGA value after LGAs are populated (another small delay)
                        setTimeout(() => {
                            if (lgaSelect) {
                                lgaSelect.value = student.local || '';
                            }
                        }, 200);
                    }, 100);
                }

                // Update form action
                const form = document.getElementById('editStudentForm');
                if (form) {
                    form.action = `/student/${id}`;
                }

            }).catch((error) => {
                console.error("Error fetching student:", error);
                Swal.fire({
                    title: "Error!",
                    text: error.response?.data?.message || "Failed to load student data",
                    icon: "error",
                    confirmButtonClass: "btn btn-primary",
                    buttonsStyling: false
                });
            });
        });
    });

    // Edit Student Form Submission
    document.getElementById('editStudentForm').addEventListener('submit', function (e) {
        e.preventDefault();
        if (!ensureAxios()) return;

        const id = document.getElementById('editStudentId').value;
        const formData = new FormData(this);
        axios.post(this.action, formData, {
            headers: { 'X-HTTP-Method-Override': 'PATCH' }
        }).then((response) => {
            Swal.fire({
                title: "Success!",
                text: "Student updated successfully",
                icon: "success",
                confirmButtonClass: "btn btn-primary",
                buttonsStyling: true
            }).then(() => {
                window.location.reload();
            });
        }).catch((error) => {
            console.error('Error updating student:', error);
            Swal.fire({
                title: "Error!",
                text: error.response?.data?.message || "Failed to update student",
                icon: "error",
                confirmButtonClass: "btn btn-primary",
                buttonsStyling: true
            });
        });
    });

    // Delete Single Student
    document.querySelectorAll('.remove-item-btn').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn btn-primary",
                cancelButtonClass: "btn btn-light",
                buttonsStyling: true
            }).then((result) => {
                if (result.isConfirmed && ensureAxios()) {
                    axios.delete(`/student/${id}`).then(() => {
                        const row = this.closest('tr');
                        if (row) row.remove();
                        studentList.reIndex();
                        Swal.fire({
                            title: "Deleted!",
                            text: "Student has been deleted",
                            icon: "success",
                            confirmButtonClass: "btn btn-primary",
                            buttonsStyling: true
                        });
                    }).catch((error) => {
                        console.error('Error deleting student:', error);
                        Swal.fire({
                            title: "Error!",
                            text: error.response?.data?.message || "Failed to delete student",
                            icon: "error",
                            confirmButtonClass: "btn btn-primary",
                            buttonsStyling: true
                        });
                    });
                }
            });
        });
    });

    // Image Preview for Add Student Modal
    document.getElementById('avatar').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('addStudentAvatar');
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    title: "Error!",
                    text: "File size exceeds 2MB limit.",
                    icon: "error",
                    confirmButtonClass: "btn btn-info",
                    buttonsStyling: false
                });
                event.target.value = '';
                preview.style.display = 'none';
                return;
            }
            const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
            if (!allowedTypes.includes(file.type)) {
                Swal.fire({
                    title: "Error!",
                    text: "Only PNG, JPG, and JPEG files are allowed.",
                    icon: "error",
                    confirmButtonClass: "btn btn-info",
                    buttonsStyling: false
                });
                event.target.value = '';
                preview.style.display = 'none';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.src = '/theme/layouts/assets/media/avatars/blank.png';
            preview.style.display = 'none';
        }
    });

    // Image Preview for Edit Student Modal
    document.getElementById('editAvatar').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('editStudentAvatar');
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    title: "Error!",
                    text: "File size exceeds 2MB limit.",
                    icon: "error",
                    confirmButtonClass: "btn btn-info",
                    buttonsStyling: false
                });
                event.target.value = '';
                preview.src = preview.getAttribute('data-original-src') || '/theme/layouts/assets/media/avatars/blank.png';
                return;
            }
            const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
            if (!allowedTypes.includes(file.type)) {
                Swal.fire({
                    title: "Error!",
                    text: "Only PNG, JPG, and JPEG files are allowed.",
                    icon: "error",
                    confirmButtonClass: "btn btn-info",
                    buttonsStyling: false
                });
                event.target.value = '';
                preview.src = preview.getAttribute('data-original-src') || '/theme/layouts/assets/media/avatars/blank.png';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            preview.src = preview.getAttribute('data-original-src') || '/theme/layouts/assets/media/avatars/blank.png';
        }
    });
});