document.addEventListener('DOMContentLoaded', function () {
    console.log('week-list.init.js: Loaded');

    // CSRF Token Setup
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) {
        console.error('CSRF token not found');
        alert('CSRF token missing. Please refresh the page.');
        return;
    }

    // Initialize List.js
    const weekList = new List('weekList', {
        valueNames: ['id', 'week_name', 'daily_entries_count', 'created_at'],
        listClass: 'list',
        searchClass: 'search',
    });

    // Initialize Daily Entry Chart
    let dailyEntryChart = null;
    const dailyChartCanvas = document.getElementById('dailyEntryChart');
    if (dailyChartCanvas) {
        dailyEntryChart = new Chart(dailyChartCanvas, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Daily Egg Production',
                        data: [],
                        borderColor: '#36A2EB',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: true,
                    },
                    {
                        label: 'Daily Mortality',
                        data: [],
                        borderColor: '#FF6384',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        fill: true,
                    },
                    {
                        label: 'Daily Feeds (kg)',
                        data: [],
                        borderColor: '#FFCE56',
                        backgroundColor: 'rgba(255, 206, 86, 0.2)',
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Value' } },
                    x: { title: { display: true, text: 'Day' } },
                },
                plugins: {
                    legend: { display: true },
                    title: { display: true, text: 'Daily Entries Metrics' },
                },
            },
        });
    }

    // Helper function to update statistics display
    function updateStats(weekStats, allWeeksStats) {
        const weekStatsContainer = document.getElementById('week-stats');
        if (weekStatsContainer && weekStats) {
            weekStatsContainer.innerHTML = `
                <h5 class="card-title">Selected Week Statistics</h5>
                <p>Total Egg Production: ${weekStats.total_egg_production} eggs</p>
                <p>Total Mortality: ${weekStats.total_mortality} birds</p>
                <p>Total Feeds Consumed: ${weekStats.total_feeds_consumed} kg</p>
                <p>Average Daily Egg Production: ${weekStats.avg_daily_egg_production.toFixed(2)} eggs</p>
                <p>Average Daily Mortality: ${weekStats.avg_daily_mortality.toFixed(2)} birds</p>
                <p>Average Daily Feeds: ${weekStats.avg_daily_feeds.toFixed(2)} kg</p>
            `;
        } else if (weekStatsContainer) {
            weekStatsContainer.innerHTML = '<p class="text-muted">Select a week to view statistics.</p>';
        }

        const allWeeksStatsContainer = document.getElementById('all-weeks-stats');
        if (allWeeksStatsContainer && allWeeksStats) {
            allWeeksStatsContainer.innerHTML = `
                <h5 class="card-title">All Weeks Statistics</h5>
                <p>Total Daily Entries: ${allWeeksStats.total_daily_entries}</p>
                <p>Total Egg Production: ${allWeeksStats.total_egg_production} eggs</p>
                <p>Total Mortality: ${allWeeksStats.total_mortality} birds</p>
                <p>Total Feeds Consumed: ${allWeeksStats.total_feeds_consumed} kg</p>
                <p>Average Daily Egg Production: ${allWeeksStats.avg_daily_egg_production.toFixed(2)} eggs</p>
                <p>Average Daily Mortality: ${allWeeksStats.avg_daily_mortality.toFixed(2)} birds</p>
                <p>Average Daily Feeds: ${allWeeksStats.avg_daily_feeds.toFixed(2)} kg</p>
            `;
        }
    }

    // Filter Data
    window.filterData = function (url = null) {
        const search = document.getElementById('searchWeek')?.value || '';
        const weekFilter = document.getElementById('weekFilter')?.value || 'all';
        const fetchUrl = url || `/flocks/${window.flockId}/weeks?search=${encodeURIComponent(search)}&week_filter=${encodeURIComponent(weekFilter)}`;
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
            weekList.clear();
            weekList.add(data.weekEntries);
            document.getElementById('pagination-element').innerHTML = data.pagination;
            document.querySelector('.badge.bg-dark-subtle').textContent = data.total;
            if (window.weekChart) {
                window.weekChart.data.labels = data.chartData.labels;
                window.weekChart.data.datasets[0].data = data.chartData.daily_entries_counts;
                window.weekChart.update();
            }
            updateStats(null, data.allWeeksStats);
            bindPagination();
            bindRowActions(); // Rebind event listeners for new rows
        })
        .catch(error => {
            console.error('Error filtering data:', error);
            let errorMsg = 'Failed to load weeks';
            try {
                const err = JSON.parse(error.message);
                errorMsg = err.status === 403 ? 'You are not authorized to view weeks' : err.error || err.message;
            } catch (e) {
                errorMsg = 'An unexpected error occurred. Please try again.';
            }
            alert(errorMsg);
        });
    };

    // Fetch Daily Entries for a Week
    function fetchDailyEntries(weekId) {
        fetch(`/flocks/${window.flockId}/weeks/${weekId}/daily-entries`, {
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
            if (dailyEntryChart) {
                dailyEntryChart.data.labels = data.dailyChartData.labels;
                dailyEntryChart.data.datasets[0].data = data.dailyChartData.daily_egg_production;
                dailyEntryChart.data.datasets[1].data = data.dailyChartData.daily_mortality;
                dailyEntryChart.data.datasets[2].data = data.dailyChartData.daily_feeds;
                dailyEntryChart.update();
            }
            updateStats(data.weekStats, null);
        })
        .catch(error => {
            console.error('Error fetching daily entries:', error);
            let errorMsg = 'Failed to load daily entries';
            try {
                const err = JSON.parse(error.message);
                errorMsg = err.status === 403 ? 'You are not authorized to view daily entries' :
                           err.status === 404 ? 'Week not found' : err.error || err.message;
            } catch (e) {
                errorMsg = 'An unexpected error occurred. Please try again.';
            }
            alert(errorMsg);
        });
    }

    // Bind Pagination
    function bindPagination() {
        document.querySelectorAll('#pagination-element .page-link').forEach(link => {
            link.removeEventListener('click', handlePaginationClick); // Prevent duplicate listeners
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

    // Bind Row Actions (Edit, Delete, Select)
    function bindRowActions() {
        document.querySelectorAll('.edit-item-btn').forEach(button => {
            button.removeEventListener('click', handleEditClick);
            button.addEventListener('click', handleEditClick);
        });
        document.querySelectorAll('.remove-item-btn').forEach(button => {
            button.removeEventListener('click', handleDeleteClick);
            button.addEventListener('click', handleDeleteClick);
        });
        document.querySelectorAll('tr').forEach(row => {
            row.removeEventListener('click', handleRowClick);
            row.addEventListener('click', handleRowClick);
        });
    }

    function handleEditClick(e) {
        const weekId = e.target.closest('tr').querySelector('.chk-child').value;
        const weekName = e.target.closest('tr').querySelector('.week_name').textContent;
        document.getElementById('edit-id-field').value = weekId;
        document.getElementById('edit_week_number').value = weekName.replace('Week ', '');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editWeekModal')).show();
    }

    function handleDeleteClick(e) {
        const weekId = e.target.closest('tr').querySelector('.chk-child').value;
        document.getElementById('delete-record').dataset.weekId = weekId;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteRecordModal')).show();
    }

    function handleRowClick(e) {
        if (e.target.closest('.edit-item-btn, .remove-item-btn, .chk-child, .btn-subtle-primary')) {
            return;
        }
        const weekId = e.target.closest('tr').querySelector('.chk-child').value;
        document.querySelectorAll('tr').forEach(row => row.classList.remove('table-active'));
        e.target.closest('tr').classList.add('table-active');
        fetchDailyEntries(weekId);
    }

    // Add Week
    const addForm = document.getElementById('add-week-form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const errorMsg = document.getElementById('add-error-msg');
            errorMsg.classList.add('d-none');
            const formData = new FormData(this);
            fetch(`/flocks/${window.flockId}/weeks`, {
                method: 'POST',
                body: formData,
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
                weekList.add(data);
                bootstrap.Modal.getInstance(document.getElementById('addWeekModal')).hide();
                window.filterData();
                alert('Week added successfully');
                addForm.reset();
            })
            .catch(error => {
                console.error('Error adding week:', error);
                let errorMsgText = 'Failed to add week';
                try {
                    const err = JSON.parse(error.message);
                    errorMsgText = err.status === 403 ? 'You are not authorized to create weeks' :
                                   err.status === 422 ? Object.values(err.errors).flat().join(', ') : err.message;
                } catch (e) {
                    errorMsgText = 'An unexpected error occurred. Please try again.';
                }
                errorMsg.textContent = errorMsgText;
                errorMsg.classList.remove('d-none');
            });
        });
    }

    // Edit Week
    const editForm = document.getElementById('edit-week-form');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const weekId = document.getElementById('edit-id-field').value;
            const errorMsg = document.getElementById('edit-error-msg');
            errorMsg.classList.add('d-none');
            const formData = new FormData(this);
            formData.append('_method', 'PUT');
            fetch(`/flocks/${window.flockId}/weeks/${weekId}`, {
                method: 'POST',
                body: formData,
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
                weekList.remove('id', data.id);
                weekList.add(data);
                bootstrap.Modal.getInstance(document.getElementById('editWeekModal')).hide();
                window.filterData();
                alert('Week updated successfully');
            })
            .catch(error => {
                console.error('Error updating week:', error);
                let errorMsgText = 'Failed to update week';
                try {
                    const err = JSON.parse(error.message);
                    errorMsgText = err.status === 403 ? 'You are not authorized to update weeks' :
                                   err.status === 422 ? Object.values(err.errors).flat().join(', ') :
                                   err.status === 404 ? 'Week not found' : err.message;
                } catch (e) {
                    errorMsgText = 'An unexpected error occurred. Please try again.';
                }
                errorMsg.textContent = errorMsgText;
                errorMsg.classList.remove('d-none');
            });
        });
    }

    // Delete Week
    const deleteButton = document.getElementById('delete-record');
    if (deleteButton) {
        deleteButton.addEventListener('click', function () {
            const weekId = this.dataset.weekId;
            fetch(`/flocks/${window.flockId}/weeks/${weekId}`, {
                method: 'DELETE',
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
                weekList.remove('id', weekId);
                bootstrap.Modal.getInstance(document.getElementById('deleteRecordModal')).hide();
                window.filterData();
                alert(data.message);
            })
            .catch(error => {
                console.error('Error deleting week:', error);
                let errorMsg = 'Failed to delete week';
                try {
                    const err = JSON.parse(error.message);
                    errorMsg = err.status === 403 ? 'You are not authorized to delete weeks' :
                               err.status === 404 ? 'Week not found' : err.message;
                } catch (e) {
                    errorMsg = 'An unexpected error occurred. Please try again.';
                }
                alert(errorMsg);
            });
        });
    }

    // Bulk Delete
    window.deleteMultiple = function () {
        const selectedIds = Array.from(document.querySelectorAll('.chk-child:checked')).map(checkbox => checkbox.value);
        if (!selectedIds.length) {
            alert('Please select at least one week to delete.');
            return;
        }
        if (!confirm(`Are you sure you want to delete ${selectedIds.length} weeks?`)) {
            return;
        }
        fetch(`/flocks/${window.flockId}/weeks/bulk-destroy`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ week_ids: selectedIds, _method: 'DELETE' }),
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw new Error(JSON.stringify({ status: response.status, ...err })); });
            }
            return response.json();
        })
        .then(data => {
            selectedIds.forEach(id => weekList.remove('id', id));
            document.getElementById('remove-actions').classList.add('d-none');
            document.getElementById('checkAll').checked = false;
            window.filterData();
            alert(data.message);
        })
        .catch(error => {
            console.error('Error deleting weeks:', error);
            let errorMsg = 'Failed to delete weeks';
            try {
                const err = JSON.parse(error.message);
                errorMsg = err.status === 403 ? 'You are not authorized to delete weeks' :
                           err.status === 422 ? Object.values(err.errors).flat().join(', ') : err.message;
            } catch (e) {
                errorMsg = 'An unexpected error occurred. Please try again.';
            }
            alert(errorMsg);
        });
    };

    // Check All
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            document.querySelectorAll('.chk-child').forEach(checkbox => checkbox.checked = this.checked);
            document.getElementById('remove-actions').classList.toggle('d-none', !this.checked);
        });
    }

    // Toggle remove-actions visibility
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('chk-child')) {
            const anyChecked = document.querySelectorAll('.chk-child:checked').length > 0;
            document.getElementById('remove-actions').classList.toggle('d-none', !anyChecked);
        }
    });

    // Initialize bindings
    bindRowActions();
    bindPagination();
});