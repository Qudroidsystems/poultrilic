// public/js/week-list.init.js
document.addEventListener('DOMContentLoaded', function () {
    const weekList = new List('weekList', {
        valueNames: ['week_name', 'daily_entries_count', 'created_at'],
        listClass: 'list',
        searchClass: 'search',
    });

    window.filterData = function () {
        const search = document.getElementById('searchWeek').value;
        const weekFilter = document.getElementById('weekFilter').value;
        fetch(`/flocks/${window.flockId}/weeks?search=${encodeURIComponent(search)}&week_filter=${weekFilter}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            return response.json();
        })
        .then(data => {
            weekList.clear();
            weekList.add(data.weekEntries);
            document.getElementById('pagination-element').innerHTML = data.pagination;
            document.querySelector('.badge.bg-dark-subtle').textContent = data.total;
            // Update chart
            if (window.weekChart) {
                window.weekChart.data.labels = data.chartData.labels;
                window.weekChart.data.datasets[0].data = data.chartData.daily_entries_counts;
                window.weekChart.update();
            }
        })
        .catch(error => console.error('Error filtering data:', error));
    };

    document.getElementById('add-week-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch(`/flocks/${window.flockId}/weeks`, {
            method: 'POST',
            body: formData,
            headers: { 
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Non-OK response:', { status: response.status, text });
                    throw new Error(`HTTP ${response.status}: ${text || response.statusText}`);
                });
            }
            return response.json().then(data => ({ status: response.status, data }));
        })
        .then(({ status, data }) => {
            if (status === 201 && data.id) {
                weekList.add(data);
                document.getElementById('addWeekModal').querySelector('.btn-close').click();
                filterData();
                alert('Week added successfully');
            } else {
                let errorMessage = 'Failed to add week';
                if (data.message) errorMessage = data.message;
                else if (data.errors) errorMessage = Object.values(data.errors).flat().join(', ');
                document.getElementById('add-error-msg').textContent = errorMessage;
                document.getElementById('add-error-msg').classList.remove('d-none');
            }
        })
        .catch(error => {
            console.error('Error adding week:', error);
            document.getElementById('add-error-msg').textContent = `An error occurred: ${error.message}`;
            document.getElementById('add-error-msg').classList.remove('d-none');
        });
    });

    document.querySelectorAll('.edit-item-btn').forEach(button => {
        button.addEventListener('click', function () {
            const weekId = this.closest('tr').querySelector('.chk-child').value;
            const weekName = this.closest('tr').querySelector('.week_name').textContent;
            document.getElementById('edit-id-field').value = weekId;
            document.getElementById('edit_week_number').value = weekName.replace('Week ', '');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editWeekModal')).show();
        });
    });

    document.getElementById('edit-week-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const weekId = document.getElementById('edit-id-field').value;
        const formData = new FormData(this);
        fetch(`/flocks/${window.flockId}/weeks/${weekId}`, {
            method: 'POST',
            body: formData,
            headers: { 
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Non-OK response:', { status: response.status, text });
                    throw new Error(`HTTP ${response.status}: ${text || response.statusText}`);
                });
            }
            return response.json().then(data => ({ status: response.status, data }));
        })
        .then(({ status, data }) => {
            if (status === 200 && data.id) {
                weekList.remove('id', data.id);
                weekList.add(data);
                document.getElementById('editWeekModal').querySelector('.btn-close').click();
                filterData();
                alert('Week updated successfully');
            } else {
                let errorMessage = 'Failed to update week';
                if (data.message) errorMessage = data.message;
                else if (data.errors) errorMessage = Object.values(data.errors).flat().join(', ');
                document.getElementById('edit-error-msg').textContent = errorMessage;
                document.getElementById('edit-error-msg').classList.remove('d-none');
            }
        })
        .catch(error => {
            console.error('Error updating week:', error);
            document.getElementById('edit-error-msg').textContent = `An error occurred: ${error.message}`;
            document.getElementById('edit-error-msg').classList.remove('d-none');
        });
    });

    document.querySelectorAll('.remove-item-btn').forEach(button => {
        button.addEventListener('click', function () {
            const weekId = this.closest('tr').querySelector('.chk-child').value;
            document.getElementById('delete-record').dataset.weekId = weekId;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteRecordModal')).show();
        });
    });

    document.getElementById('delete-record').addEventListener('click', function () {
        const weekId = this.dataset.weekId;
        fetch(`/flocks/${window.flockId}/weeks/${weekId}`, {
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
            weekList.remove('id', weekId);
            document.getElementById('deleteRecordModal').querySelector('.btn-close').click();
            filterData();
            alert(data.message);
        })
        .catch(error => console.error('Error deleting week:', error));
    });

    window.deleteMultiple = function () {
        const selectedIds = Array.from(document.querySelectorAll('.chk-child:checked')).map(checkbox => checkbox.value);
        if (selectedIds.length) {
            Promise.all(selectedIds.map(id =>
                fetch(`/flocks/${window.flockId}/weeks/${id}`, {
                    method: 'DELETE',
                    headers: { 
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
            ))
            .then(() => {
                selectedIds.forEach(id => weekList.remove('id', id));
                document.getElementById('remove-actions').classList.add('d-none');
                filterData();
                alert('Weeks deleted successfully');
            })
            .catch(error => console.error('Error deleting weeks:', error));
        }
    };

    document.getElementById('checkAll').addEventListener('change', function () {
        document.querySelectorAll('.chk-child').forEach(checkbox => checkbox.checked = this.checked);
        document.getElementById('remove-actions').classList.toggle('d-none', !this.checked);
    });

    document.querySelectorAll('.chk-child').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const anyChecked = document.querySelectorAll('.chk-child:checked').length > 0;
            document.getElementById('remove-actions').classList.toggle('d-none', !anyChecked);
        });
    });
});